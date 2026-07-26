<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Provider;

final class OpenAIProvider extends AbstractOpenAICompatibleProvider
{
    private const BASE_URL = 'https://api.openai.com/v1/chat/completions';

    private const DEFAULT_MODEL = 'gpt-4o-mini';

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
    ) {
        parent::__construct($apiKey, $baseUrl);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? self::BASE_URL;
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    public function getApiKeyEnvVar(): string
    {
        return 'OPENAI_API_KEY';
    }
}
