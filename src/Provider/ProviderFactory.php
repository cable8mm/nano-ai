<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Provider;

use Cable8mm\NanoAI\Exception\AuthenticationException;

final class ProviderFactory
{
    /**
     * @param  array<string, mixed>  $options  Provider-specific options from Client
     *                                         (e.g., OpenRouter's 'referer', 'title')
     */
    public static function make(string $providerName, ?string $apiKey, array $options = []): ProviderInterface
    {
        $normalized = strtolower(trim($providerName));

        return match ($normalized) {
            'openai' => new OpenAIProvider(
                self::resolveApiKey($apiKey, 'OPENAI_API_KEY', $normalized),
                $options['baseUrl'] ?? null,
            ),
            'openrouter' => new OpenRouterProvider(
                self::resolveApiKey($apiKey, 'OPENROUTER_API_KEY', $normalized),
                $options,
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported provider: '{$providerName}' (available: openai, openrouter)"
            ),
        };
    }

    /**
     * If apiKey is not explicitly provided to the constructor, automatically looks it up
     * from the conventional environment variable name, following the "zero-config" philosophy.
     */
    private static function resolveApiKey(?string $apiKey, string $envVar, string $providerName): string
    {
        if ($apiKey !== null && trim($apiKey) !== '') {
            return $apiKey;
        }

        $fromEnv = getenv($envVar);

        if ($fromEnv === false || trim($fromEnv) === '') {
            throw new AuthenticationException(
                "No API Key for '{$providerName}' provider. "
                ."Pass it directly to the Client constructor or set the {$envVar} environment variable."
            );
        }

        return $fromEnv;
    }
}
