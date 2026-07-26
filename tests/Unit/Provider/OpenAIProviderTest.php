<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Exception\ApiException;
use Cable8mm\NanoAI\Exception\AuthenticationException;
use Cable8mm\NanoAI\Provider\OpenAIProvider;

it('throws AuthenticationException when API key is empty', function () {
    new OpenAIProvider('');
})->throws(AuthenticationException::class);

it('returns default base URL, model, and env var name', function () {
    $provider = new OpenAIProvider('sk-test');

    expect($provider->getBaseUrl())->toBe('https://api.openai.com/v1/chat/completions')
        ->and($provider->getDefaultModel())->toBe('gpt-4o-mini')
        ->and($provider->getApiKeyEnvVar())->toBe('OPENAI_API_KEY');
});

it('uses baseUrl override when provided', function () {
    $provider = new OpenAIProvider('sk-test', 'http://127.0.0.1:9000/mock');

    expect($provider->getBaseUrl())->toBe('http://127.0.0.1:9000/mock');
});

it('creates Authorization header in Bearer format', function () {
    $provider = new OpenAIProvider('sk-abc123');

    expect($provider->getHeaders())->toBe(['Authorization' => 'Bearer sk-abc123']);
});

it('creates content as plain string when no image', function () {
    $provider = new OpenAIProvider('sk-test');

    $payload = $provider->buildPayload('gpt-4o-mini', 'Hello', null);

    expect($payload)->toBe([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello'],
        ],
    ]);
});

it('creates multimodal content array when image is present', function () {
    $provider = new OpenAIProvider('sk-test');

    $payload = $provider->buildPayload('gpt-4o-mini', 'Describe this', 'https://example.com/cat.jpg');

    expect($payload['messages'][0]['content'])->toBe([
        ['type' => 'text', 'text' => 'Describe this'],
        ['type' => 'image_url', 'image_url' => ['url' => 'https://example.com/cat.jpg']],
    ]);
});

it('extracts text from choices[0].message.content', function () {
    $provider = new OpenAIProvider('sk-test');

    $text = $provider->extractText([
        'choices' => [['message' => ['content' => 'result text']]],
    ]);

    expect($text)->toBe('result text');
});

it('throws ApiException when content field is missing', function () {
    $provider = new OpenAIProvider('sk-test');

    $provider->extractText(['choices' => [['message' => []]]]);
})->throws(ApiException::class);

it('throws ApiException when content is not a string', function () {
    $provider = new OpenAIProvider('sk-test');

    $provider->extractText(['choices' => [['message' => ['content' => ['unexpected' => 'array']]]]]);
})->throws(ApiException::class);
