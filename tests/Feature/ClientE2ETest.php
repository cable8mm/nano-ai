<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Client;
use Cable8mm\NanoAI\Exception\AuthenticationException;

/*
|--------------------------------------------------------------------------
| End-to-End Tests (Real API Calls)
|--------------------------------------------------------------------------
| These tests make real API calls to OpenAI/OpenRouter and require:
|   - RUN_E2E_TESTS=1 environment variable
|   - Valid API keys (OPENAI_API_KEY, OPENROUTER_API_KEY)
|
| Run with:
|   RUN_E2E_TESTS=1 OPENAI_API_KEY=sk-... OPENROUTER_API_KEY=sk-... composer test:e2e
|
| These tests are opt-in and skipped by default. They make real API calls
| and may incur costs.
*/

uses()->group('e2e');

$skipE2E = ! getenv('RUN_E2E_TESTS');
$skipMsg = 'Set RUN_E2E_TESTS=1 and provide API keys to run e2e tests.';

it('generates text with OpenAI API', function () {
    $client = new Client('openai', getenv('OPENAI_API_KEY') ?: null);

    $result = $client->generate('Say "hello world" in exactly 3 words.');

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

it('generates text with OpenRouter API', function () {
    $client = new Client('openrouter', getenv('OPENROUTER_API_KEY') ?: null);

    $result = $client->generate('Say "hello world" in exactly 3 words.');

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

it('generates text with a specific model via OpenRouter', function () {
    $client = new Client(
        'openrouter',
        getenv('OPENROUTER_API_KEY') ?: null,
        'openai/gpt-4o-mini',
    );

    $result = $client->generate('What is 2+2? Answer with just the number.');

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

it('handles multimodal (image) requests with OpenAI', function () {
    $client = new Client('openai', getenv('OPENAI_API_KEY') ?: null);

    $imageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Cat03.jpg/640px-Cat03.jpg';

    $result = $client->generate('Describe this image in one sentence.', $imageUrl);

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

it('throws AuthenticationException with an invalid API key', function () {
    $client = new Client('openai', 'sk-invalid-key');

    $client->generate('Hello');
})->throws(AuthenticationException::class)
    ->skip($skipE2E, $skipMsg);
