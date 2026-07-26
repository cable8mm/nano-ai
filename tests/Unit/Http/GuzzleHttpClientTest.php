<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Exception\NetworkException;
use Cable8mm\NanoAI\Http\GuzzleHttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

beforeAll(function () {
    if (! class_exists(Client::class)) {
        markTestSkipped('Guzzle is not installed. Run: composer require guzzlehttp/guzzle');
    }
});

it('returns status code and body from a successful response', function () {
    $mock = new MockHandler([
        new Response(200, [], '{"choices":[{"message":{"content":"Hello!"}}]}'),
    ]);

    $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    [$statusCode, $body] = $client->post('https://api.example.com/v1/chat/completions', [
        'Authorization' => 'Bearer sk-test',
    ], ['model' => 'gpt-4o-mini', 'messages' => []]);

    expect($statusCode)->toBe(200)
        ->and($body)->toBe('{"choices":[{"message":{"content":"Hello!"}}]}');
});

it('returns 4xx/5xx status codes without throwing (http_errors disabled)', function () {
    $mock = new MockHandler([
        new Response(401, [], '{"error":{"message":"Invalid API key"}}'),
    ]);

    $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    [$statusCode, $body] = $client->post('https://api.example.com/v1/chat/completions', [], []);

    expect($statusCode)->toBe(401)
        ->and($body)->toBe('{"error":{"message":"Invalid API key"}}');
});

it('includes Content-Type and Authorization headers in the request', function () {
    $mock = new MockHandler([
        new Response(200, [], '{}'),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new GuzzleHttpClient(new Client(['handler' => $handlerStack]));

    $client->post('https://api.example.com/v1/chat/completions', [
        'Authorization' => 'Bearer sk-secret',
    ], ['model' => 'gpt-4o-mini']);

    $lastRequest = $mock->getLastRequest();

    expect($lastRequest)->not->toBeNull()
        ->and($lastRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($lastRequest->getHeaderLine('Authorization'))->toBe('Bearer sk-secret');
});

it('sends the JSON-encoded payload as the request body', function () {
    $mock = new MockHandler([
        new Response(200, [], '{}'),
    ]);

    $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    $payload = ['model' => 'gpt-4o-mini', 'messages' => [['role' => 'user', 'content' => 'Hello']]];

    $client->post('https://api.example.com/v1/chat/completions', [], $payload);

    $lastRequest = $mock->getLastRequest();

    expect($lastRequest)->not->toBeNull()
        ->and($lastRequest->getBody()->getContents())
        ->toBe(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
});

it('throws NetworkException when Guzzle encounters a connection error', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Connection refused',
            new Request('POST', 'https://api.example.com/v1/chat/completions')
        ),
    ]);

    $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    $client->post('https://api.example.com/v1/chat/completions', [], []);
})->throws(NetworkException::class);

it('throws NetworkException when payload cannot be JSON-encoded', function () {
    $mock = new MockHandler([
        new Response(200, [], '{}'),
    ]);

    $client = new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    // NAN cannot be represented in JSON, so json_encode() returns false.
    $client->post('https://api.example.com/v1/chat/completions', [], ['bad' => NAN]);
})->throws(NetworkException::class);

it('creates a default Guzzle client when none is provided', function () {
    // This test verifies that the constructor can create a Guzzle client internally
    // without errors. We don't make an actual request.
    $client = new GuzzleHttpClient(timeoutSeconds: 5, connectTimeoutSeconds: 3);

    expect($client)->toBeInstanceOf(GuzzleHttpClient::class);
});
