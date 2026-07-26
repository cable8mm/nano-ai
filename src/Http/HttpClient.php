<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Http;

use Cable8mm\NanoAI\Exception\NetworkException;

/**
 * Minimal HTTP client wrapping cURL.
 * Implemented directly to avoid depending on external HTTP client libraries like Guzzle.
 */
final class HttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly int $timeoutSeconds = 30,
        private readonly int $connectTimeoutSeconds = 10,
    ) {}

    /**
     * Sends a JSON POST request and returns [statusCode, rawBody].
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     * @return array{0: int, 1: string}
     *
     * @throws NetworkException When cURL itself fails (timeout, DNS failure, etc.)
     */
    public function post(string $url, array $headers, array $payload): array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new NetworkException('Failed to initialize cURL handle.');
        }

        $formattedHeaders = ['Content-Type: application/json'];
        foreach ($headers as $name => $value) {
            $formattedHeaders[] = "{$name}: {$value}";
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encodedPayload === false) {
            curl_close($ch);
            throw new NetworkException('Failed to encode request payload as JSON: '.json_last_error_msg());
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encodedPayload,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_FAILONERROR => false, // We read the body ourselves to handle 4xx/5xx.
        ]);

        $responseBody = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorMessage = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errorNumber !== 0 || $responseBody === false) {
            throw new NetworkException(
                sprintf('Network request failed (cURL errno %d): %s', $errorNumber, $errorMessage ?: 'Unknown error')
            );
        }

        return [$statusCode, $responseBody];
    }
}
