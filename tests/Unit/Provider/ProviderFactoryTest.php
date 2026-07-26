<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Exception\AuthenticationException;
use Cable8mm\NanoAI\Provider\OpenAIProvider;
use Cable8mm\NanoAI\Provider\OpenRouterProvider;
use Cable8mm\NanoAI\Provider\ProviderFactory;

afterEach(function () {
    // Prevent env var leakage between tests
    putenv('OPENAI_API_KEY');
    putenv('OPENROUTER_API_KEY');
});

it('creates OpenAIProvider from "openai" string', function () {
    expect(ProviderFactory::make('openai', 'sk-test'))->toBeInstanceOf(OpenAIProvider::class);
});

it('creates OpenRouterProvider from "openrouter" string', function () {
    expect(ProviderFactory::make('openrouter', 'or-test'))->toBeInstanceOf(OpenRouterProvider::class);
});

it('is case-insensitive and trims whitespace in provider name', function (string $name) {
    expect(ProviderFactory::make($name, 'sk-test'))->toBeInstanceOf(OpenAIProvider::class);
})->with(['OPENAI', 'OpenAI', ' openai ']);

it('throws InvalidArgumentException for unsupported provider name', function () {
    ProviderFactory::make('anthropic-direct', 'x');
})->throws(InvalidArgumentException::class);

it('auto-finds apiKey from conventional env var when omitted', function () {
    putenv('OPENAI_API_KEY=sk-from-env');

    $provider = ProviderFactory::make('openai', null);

    expect($provider->getHeaders())->toBe(['Authorization' => 'Bearer sk-from-env']);
});

it('prioritizes explicitly passed apiKey over env var', function () {
    putenv('OPENAI_API_KEY=sk-from-env');

    $provider = ProviderFactory::make('openai', 'sk-explicit');

    expect($provider->getHeaders())->toBe(['Authorization' => 'Bearer sk-explicit']);
});

it('throws AuthenticationException when no apiKey and no env var', function () {
    ProviderFactory::make('openai', null);
})->throws(AuthenticationException::class);

it('passes baseUrl option to both openai and openrouter', function (string $provider) {
    $instance = ProviderFactory::make($provider, 'test-key', ['baseUrl' => 'http://127.0.0.1:9999/x']);

    expect($instance->getBaseUrl())->toBe('http://127.0.0.1:9999/x');
})->with(['openai', 'openrouter']);
