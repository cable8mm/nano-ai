<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Tests\Support;

use Cable8mm\NanoAI\Http\HttpClientInterface;

/**
 * Test double that returns a pre-defined status code/body without hitting the real network.
 * Records the last URL/headers/payload that was requested, allowing verification that
 * the Client correctly built and passed the request to the provider.
 */
final class FakeHttpClient implements HttpClientInterface
{
    /** @var array{url: string, headers: array<string, string>, payload: array<string, mixed>}|null */
    public ?array $lastRequest = null;

    public function __construct(
        private readonly int $statusCode,
        private readonly string $responseBody,
    ) {}

    public function post(string $url, array $headers, array $payload): array
    {
        $this->lastRequest = ['url' => $url, 'headers' => $headers, 'payload' => $payload];

        return [$this->statusCode, $this->responseBody];
    }
}
