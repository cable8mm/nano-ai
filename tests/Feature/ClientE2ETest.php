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
|   - tests/config.json with valid API keys (copy from tests/config.json.example)
|
| Run with:
|   RUN_E2E_TESTS=1 composer test:e2e
|
| These tests are opt-in and skipped by default. They make real API calls
| and may incur costs.
*/

// Load API keys from tests/config.json (gitignored) if it exists.
$configFile = __DIR__.'/../config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (is_array($config)) {
        foreach ($config as $key => $value) {
            putenv("{$key}={$value}");
        }
    }
}

uses()->group('e2e');

$skipE2E = ! getenv('RUN_E2E_TESTS');
$skipMsg = 'Set RUN_E2E_TESTS=1 and create tests/config.json with API keys to run e2e tests.';

// it('generates text with OpenAI API', function () {
//     $client = new Client(
//         'openai',
//         getenv('OPENAI_API_KEY') ?: null,
//         'gpt-5.6-luna'
//     );

//     $result = $client->generate('Say "hello world" in exactly 3 words.');

//     expect($result)->toBeString()->not->toBeEmpty();
// })->skip($skipE2E, $skipMsg);

it('generates text with OpenRouter API', function () {
    $client = new Client(
        'openrouter',
        getenv('OPENROUTER_API_KEY') ?: null,
        'google/gemma-4-26b-a4b-it:free'
    );

    $result = $client->generate('Say "hello world" in exactly 3 words.');

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

it('generates text with a specific model via OpenRouter', function () {
    $client = new Client(
        'openrouter',
        getenv('OPENROUTER_API_KEY') ?: null,
        'google/gemma-4-26b-a4b-it:free',
    );

    $result = $client->generate('What is 2+2? Answer with just the number.');

    expect($result)->toBeString()->not->toBeEmpty();
})->skip($skipE2E, $skipMsg);

// it('handles multimodal (image) requests with OpenAI', function () {
//     $client = new Client(
//         'openai',
//         getenv('OPENAI_API_KEY') ?: null,
//         'gpt-5.6-luna');

//     $imagePath = __DIR__.'/../Fixtures/circle.png';

//     $result = $client->generate('Describe this image in one sentence.', $imagePath);

//     expect($result)->toBeString()->not->toBeEmpty();
// })->skip($skipE2E, $skipMsg);

it('throws AuthenticationException with an invalid API key', function () {
    $client = new Client('openai', 'sk-invalid-key');

    $client->generate('Hello');
})->throws(AuthenticationException::class)
    ->skip($skipE2E, $skipMsg);
