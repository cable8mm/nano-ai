<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Provider\OpenRouterProvider;

it('returns default base URL, model, and env var name', function () {
    $provider = new OpenRouterProvider('or-test');

    expect($provider->getBaseUrl())->toBe('https://openrouter.ai/api/v1/chat/completions')
        ->and($provider->getDefaultModel())->toBe('openai/gpt-4o-mini')
        ->and($provider->getApiKeyEnvVar())->toBe('OPENROUTER_API_KEY');
});

it('includes referer/title in headers when provided', function () {
    $provider = new OpenRouterProvider('or-test', [
        'referer' => 'https://repl.net',
        'title' => 'nano-ai test',
    ]);

    expect($provider->getHeaders())->toBe([
        'Authorization' => 'Bearer or-test',
        'HTTP-Referer' => 'https://repl.net',
        'X-Title' => 'nano-ai test',
    ]);
});

it('omits referer/title headers when not provided', function () {
    $provider = new OpenRouterProvider('or-test');

    expect($provider->getHeaders())->toBe(['Authorization' => 'Bearer or-test']);
});

it('overrides endpoint via baseUrl option', function () {
    $provider = new OpenRouterProvider('or-test', ['baseUrl' => 'http://127.0.0.1:9001/mock']);

    expect($provider->getBaseUrl())->toBe('http://127.0.0.1:9001/mock');
});

it('passes free model names (:free suffix) as-is', function () {
    $provider = new OpenRouterProvider('or-test');

    $payload = $provider->buildPayload('deepseek/deepseek-chat:free', 'test', null);

    expect($payload['model'])->toBe('deepseek/deepseek-chat:free');
});

it('passes Chinese/open-source model names without restriction', function (string $model) {
    $provider = new OpenRouterProvider('or-test');

    $payload = $provider->buildPayload($model, 'test', null);

    expect($payload['model'])->toBe($model);
})->with([
    'deepseek/deepseek-chat',
    'qwen/qwen-2.5-7b-instruct:free',
    'google/gemini-2.0-flash-001',
]);
