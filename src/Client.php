<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI;

use Cable8mm\NanoAI\Exception\ApiException;
use Cable8mm\NanoAI\Exception\AuthenticationException;
use Cable8mm\NanoAI\Exception\NanoAIException;
use Cable8mm\NanoAI\Exception\RateLimitException;
use Cable8mm\NanoAI\Http\HttpClient;
use Cable8mm\NanoAI\Http\HttpClientInterface;
use Cable8mm\NanoAI\Provider\ProviderFactory;
use Cable8mm\NanoAI\Provider\ProviderInterface;

/**
 * NanoAI\Client
 *
 * Ultra-lightweight AI SDK supporting only text generation + multimodal (text+image) processing.
 * Agents, RAG, streaming, function calling, etc. are intentionally not supported.
 *
 * Supported providers: 'openai', 'openrouter'
 * Using OpenRouter allows you to cover most providers (DeepSeek, Qwen, Gemini, Llama, etc.)
 * with a single API key by just changing the model name.
 */
final class Client
{
    private readonly ProviderInterface $provider;

    private readonly string $model;

    private readonly HttpClientInterface $http;

    /**
     * @param  string  $provider  'openai' | 'openrouter'
     * @param  string|null  $apiKey  If null, automatically looks up the provider's conventional environment variable (OPENAI_API_KEY, etc.).
     * @param  string|null  $model  If null, uses the provider's default model.
     * @param array{
     *   timeout?: int,
     *   connectTimeout?: int,
     *   baseUrl?: string,
     *   referer?: string,
     *   title?: string
     * } $options
     * @param  HttpClientInterface|null  $httpClient  If null, a real cURL-based HttpClient is created.
     */
    public function __construct(
        string $provider = 'openai',
        ?string $apiKey = null,
        ?string $model = null,
        array $options = [],
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->provider = ProviderFactory::make($provider, $apiKey, $options);
        $this->model = $model ?? $this->provider->getDefaultModel();
        $this->http = $httpClient ?? new HttpClient(
            timeoutSeconds: $options['timeout'] ?? 120,
            connectTimeoutSeconds: $options['connectTimeout'] ?? 30,
        );
    }

    /**
     * Sends a text (+ optional image) prompt and returns only the response text.
     *
     * @param  string  $prompt  Text prompt
     * @param  string|null  $imageUrl  Supports all three forms:
     *                                 - "https://..." URL → sent as-is
     *                                 - "data:image/..." data URI → sent as-is
     *                                 - Local file path (e.g. "/path/to/photo.jpg") → automatically read and converted to base64 data URI
     *
     * @throws AuthenticationException API Key is missing or invalid (401/403)
     * @throws RateLimitException Request rate limit exceeded (429)
     * @throws ApiException Other API error responses or response parsing failure
     * @throws NetworkException Network-level failures such as timeouts
     */
    public function generate(string $prompt, ?string $imageUrl = null): string
    {
        $resolvedImage = $imageUrl !== null ? $this->resolveImage($imageUrl) : null;

        $payload = $this->provider->buildPayload($this->model, $prompt, $resolvedImage);

        [$statusCode, $rawBody] = $this->http->post(
            $this->provider->getBaseUrl(),
            $this->provider->getHeaders(),
            $payload,
        );

        $decoded = json_decode($rawBody, true);

        if (! is_array($decoded)) {
            throw new ApiException(
                'Failed to parse response as JSON.',
                $statusCode,
                $rawBody,
            );
        }

        if ($statusCode >= 400) {
            throw $this->exceptionForErrorResponse($statusCode, $decoded, $rawBody);
        }

        return $this->provider->extractText($decoded);
    }

    /**
     * Accepts URL / data URI / local file path and normalizes it into a
     * URL or base64 data URI string that can be sent directly to the API.
     */
    private function resolveImage(string $imageUrl): string
    {
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        if (str_starts_with($imageUrl, 'data:')) {
            return $imageUrl;
        }

        // Otherwise, treat it as a local file path (binary image) and convert to base64 data URI.
        if (! is_file($imageUrl) || ! is_readable($imageUrl)) {
            throw new ApiException("Image file not found or unreadable: {$imageUrl}");
        }

        $binary = file_get_contents($imageUrl);
        if ($binary === false) {
            throw new ApiException("Failed to read image file: {$imageUrl}");
        }

        $mimeType = function_exists('mime_content_type')
            ? (mime_content_type($imageUrl) ?: 'image/jpeg')
            : 'image/jpeg';

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($binary));
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function exceptionForErrorResponse(int $statusCode, array $decoded, string $rawBody): NanoAIException
    {
        $message = $decoded['error']['message']
            ?? $decoded['message']
               ?? "API request failed (HTTP {$statusCode})";

        return match (true) {
            $statusCode === 401 || $statusCode === 403 => new AuthenticationException($message),
            $statusCode === 429 => new RateLimitException($message),
            default => new ApiException($message, $statusCode, $rawBody),
        };
    }
}
