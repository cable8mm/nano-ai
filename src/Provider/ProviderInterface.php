<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Provider;

/**
 * To add a new provider, simply implement this interface.
 * (e.g., Groq, direct Anthropic connection, etc. — no need to modify Client or HttpClient)
 */
interface ProviderInterface
{
    /**
     * Full URL of the Chat Completions endpoint.
     */
    public function getBaseUrl(): string;

    /**
     * Headers required for the request (Content-Type is automatically added by HttpClient).
     *
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * Default model name for this provider.
     */
    public function getDefaultModel(): string;

    /**
     * Converts the prompt (+ optional image) into a request body compatible with this provider's API spec.
     *
     * @param  string|null  $resolvedImage  Image value already normalized as URL or data URI
     * @return array<string, mixed>
     */
    public function buildPayload(string $model, string $prompt, ?string $resolvedImage): array;

    /**
     * Extracts only the text body from the decoded JSON response.
     *
     * @param  array<string, mixed>  $responseData
     */
    public function extractText(array $responseData): string;
}
