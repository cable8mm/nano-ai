<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Client;
use Cable8mm\NanoAI\Exception\ApiException;
use Cable8mm\NanoAI\Exception\AuthenticationException;
use Cable8mm\NanoAI\Exception\RateLimitException;
use Cable8mm\NanoAI\Tests\Support\FakeHttpClient;

afterEach(function () {
    putenv('OPENAI_API_KEY');
});

it('returns text as-is from a successful response', function () {
    $fake = fakeSuccess('Hello!');
    $client = new Client('openai', 'sk-test', null, [], $fake);

    expect($client->generate('Say hello'))->toBe('Hello!');
});

it('sends requests with the default model (gpt-4o-mini)', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openai', 'sk-test', null, [], $fake);

    $client->generate('anything');

    expect($fake->lastRequest['payload']['model'])->toBe('gpt-4o-mini');
});

it('uses the specified model when model argument is provided', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openai', 'sk-test', 'gpt-4o', [], $fake);

    $client->generate('anything');

    expect($fake->lastRequest['payload']['model'])->toBe('gpt-4o');
});

it('includes image_url in payload when imageUrl is provided', function () {
    $fake = fakeSuccess('description done');
    $client = new Client('openai', 'sk-test', null, [], $fake);

    $client->generate('Describe this', 'https://example.com/cat.jpg');

    expect($fake->lastRequest['payload']['messages'][0]['content'][1]['image_url']['url'])
        ->toBe('https://example.com/cat.jpg');
});

it('uses plain string for content when imageUrl is not provided', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openai', 'sk-test', null, [], $fake);

    $client->generate('Just text');

    expect($fake->lastRequest['payload']['messages'][0]['content'])->toBe('Just text');
});

it('sends the Authorization header', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openai', 'sk-secret-123', null, [], $fake);

    $client->generate('anything');

    expect($fake->lastRequest['headers']['Authorization'])->toBe('Bearer sk-secret-123');
});

it('throws AuthenticationException on 401 response', function () {
    $client = new Client('openai', 'sk-test', null, [], fakeError(401, 'Invalid API key'));

    $client->generate('anything');
})->throws(AuthenticationException::class, 'Invalid API key');

it('throws RateLimitException on 429 response', function () {
    $client = new Client('openai', 'sk-test', null, [], fakeError(429, 'Rate limit exceeded'));

    $client->generate('anything');
})->throws(RateLimitException::class, 'Rate limit exceeded');

it('throws ApiException on 500 response and preserves status code/body', function () {
    $client = new Client('openai', 'sk-test', null, [], fakeError(500, 'Server error'));

    try {
        $client->generate('anything');
        $this->fail('An exception should have been thrown.');
    } catch (ApiException $e) {
        expect($e->getStatusCode())->toBe(500)
            ->and($e->getMessage())->toBe('Server error')
            ->and($e->getResponseBody())->toContain('Server error');
    }
});

it('throws ApiException when response cannot be parsed as JSON', function () {
    $client = new Client('openai', 'sk-test', null, [], new FakeHttpClient(200, 'not valid json'));

    $client->generate('anything');
})->throws(ApiException::class);

it('works with OPENAI_API_KEY env var when apiKey is not passed', function () {
    putenv('OPENAI_API_KEY=sk-from-env');

    $fake = fakeSuccess('authenticated via env');
    $client = new Client('openai', null, null, [], $fake);

    expect($client->generate('anything'))->toBe('authenticated via env');
    expect($fake->lastRequest['headers']['Authorization'])->toBe('Bearer sk-from-env');
});

it('throws AuthenticationException when neither apiKey nor env var is set', function () {
    new Client('openai');
})->throws(AuthenticationException::class);

it('throws InvalidArgumentException for unsupported provider', function () {
    new Client('some-unknown-provider', 'x');
})->throws(InvalidArgumentException::class);

it('uses a different default model for OpenRouter provider', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openrouter', 'or-test', null, [], $fake);

    $client->generate('anything');

    expect($fake->lastRequest['payload']['model'])->toBe('openai/gpt-4o-mini');
});

it('passes free/Chinese model names as-is in the request body for OpenRouter', function () {
    $fake = fakeSuccess('ok');
    $client = new Client('openrouter', 'or-test', 'deepseek/deepseek-chat:free', [], $fake);

    $client->generate('test');

    expect($fake->lastRequest['payload']['model'])->toBe('deepseek/deepseek-chat:free');
});
