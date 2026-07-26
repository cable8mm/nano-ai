<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Provider;

use Cable8mm\NanoAI\Exception\ApiException;
use Cable8mm\NanoAI\Exception\AuthenticationException;

/**
 * Many providers (OpenAI, OpenRouter, DeepSeek, Qwen, various open-source models, etc.)
 * use nearly the same request/response format as OpenAI Chat Completions.
 * Common logic is collected in this abstract class, and subclasses only need to
 * specify different URLs, headers, and default models.
 */
abstract class AbstractOpenAICompatibleProvider implements ProviderInterface
{
    public function __construct(
        protected readonly string $apiKey,
        protected readonly ?string $baseUrl = null,
    ) {
        if (trim($apiKey) === '') {
            throw new AuthenticationException(
                static::class.': API Key is empty. Pass it to the constructor or '
                .'set the '.$this->getApiKeyEnvVar().' environment variable.'
            );
        }
    }

    /**
     * The environment variable name for this provider's API Key (for Client's zero-config fallback).
     */
    abstract public function getApiKeyEnvVar(): string;

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }

    public function buildPayload(string $model, string $prompt, ?string $resolvedImage): array
    {
        $content = [
            ['type' => 'text', 'text' => $prompt],
        ];

        if ($resolvedImage !== null) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $resolvedImage],
            ];
        }

        return [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    // When there's no image, sending a plain string instead of wrapping it in an array
                    // provides better compatibility with some implementations (especially older OpenAI-compatible servers).
                    'content' => $resolvedImage === null ? $prompt : $content,
                ],
            ],
        ];
    }

    public function extractText(array $responseData): string
    {
        $text = $responseData['choices'][0]['message']['content'] ?? null;

        if (! is_string($text)) {
            throw new ApiException(
                'Could not find text in response. choices[0].message.content is missing or not a string.',
                null,
                json_encode($responseData, JSON_UNESCAPED_UNICODE)
            );
        }

        return $text;
    }
}
