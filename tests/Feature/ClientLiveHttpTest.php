<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Client;
use Cable8mm\NanoAI\Exception\ApiException;
use Cable8mm\NanoAI\Exception\AuthenticationException;
use Cable8mm\NanoAI\Exception\RateLimitException;
use Cable8mm\NanoAI\Tests\Support\MockServer;

/*
|--------------------------------------------------------------------------
| Tests that use the real HttpClient (cURL) round-tripping to a local mock server.
|--------------------------------------------------------------------------
| Since the FakeHttpClient-based tests (ClientTest.php) only verify Client logic,
| this separately verifies that HttpClient's actual cURL integration (header sending,
| status code handling, timeout options, etc.) is properly wired up.
| No external network is accessed (only 127.0.0.1 is used).
*/

beforeAll(fn () => MockServer::start(8973));
afterAll(fn () => MockServer::stop());

it('receives normal text via real cURL request-response round trip', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/success',
    ]);

    expect($client->generate('Hello'))->toBe('mock server response');
});

it('sends a request with image actually including the image_url field', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/echo-has-image',
    ]);

    expect($client->generate('Describe this', 'https://example.com/cat.jpg'))->toBe('image received');
});

it('sends a local image file converted to base64', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'nano_ai_live_').'.png';
    file_put_contents(
        $tmpFile,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
    );

    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/echo-has-image',
    ]);

    expect($client->generate('What is this', $tmpFile))->toBe('image received');

    unlink($tmpFile);
});

it('converts real 401 HTTP response to AuthenticationException', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/auth-error',
    ]);

    $client->generate('anything');
})->throws(AuthenticationException::class, 'Invalid API key');

it('converts real 429 HTTP response to RateLimitException', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/rate-limit',
    ]);

    $client->generate('anything');
})->throws(RateLimitException::class, 'Rate limit exceeded');

it('converts real 500 HTTP response to ApiException', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/server-error',
    ]);

    $client->generate('anything');
})->throws(ApiException::class, 'Internal server error');

it('throws ApiException for malformed JSON response', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/malformed-json',
    ]);

    $client->generate('anything');
})->throws(ApiException::class);

it('handles non-existent route (404) as ApiException', function () {
    $client = new Client('openai', 'sk-test', null, [
        'baseUrl' => MockServer::baseUrl().'/no-such-route',
    ]);

    $client->generate('anything');
})->throws(ApiException::class);
