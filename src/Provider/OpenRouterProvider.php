<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Provider;

/**
 * OpenRouter (https://openrouter.ai) proxies dozens to hundreds of models
 * (OpenAI, Anthropic, Google, Meta, DeepSeek, Qwen, etc.) using nearly the same
 * format as OpenAI Chat Completions.
 *
 * Model name examples:
 *   - 'openai/gpt-4o-mini'
 *   - 'google/gemini-2.0-flash-001'
 *   - 'deepseek/deepseek-chat'
 *   - 'deepseek/deepseek-chat:free'   (free tier, :free suffix after model name)
 *   - 'qwen/qwen-2.5-7b-instruct:free'
 *
 * The list of free models changes frequently on OpenRouter's side, so it is
 * recommended to check the latest list at https://openrouter.ai/models?max_price=0
 * before use.
 */
final class OpenRouterProvider extends AbstractOpenAICompatibleProvider
{
    private const BASE_URL = 'https://openrouter.ai/api/v1/chat/completions';

    // Free model lists change frequently, so the default is a paid but affordable and stable model.
    // To use free/Chinese models, specify the $model argument directly when creating the Client.
    private const DEFAULT_MODEL = 'openai/gpt-4o-mini';

    /**
     * @param  array{referer?: string, title?: string}  $options
     *                                                            referer and title are "recommended" by OpenRouter for request source identification,
     *                                                            but the API call works fine without them.
     */
    public function __construct(
        string $apiKey,
        private readonly array $options = [],
    ) {
        parent::__construct($apiKey, $options['baseUrl'] ?? null);
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
        return 'OPENROUTER_API_KEY';
    }

    public function getHeaders(): array
    {
        $headers = parent::getHeaders();

        if (! empty($this->options['referer'])) {
            $headers['HTTP-Referer'] = $this->options['referer'];
        }

        if (! empty($this->options['title'])) {
            $headers['X-Title'] = $this->options['title'];
        }

        return $headers;
    }
}
