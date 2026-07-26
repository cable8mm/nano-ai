<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Http;

use Cable8mm\NanoAI\Exception\NetworkException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Guzzle-based HTTP client implementation.
 *
 * This is an optional alternative to the default cURL-based HttpClient.
 * Guzzle is NOT a required dependency — install it separately:
 *
 *   composer require guzzlehttp/guzzle
 *
 * Then inject this class when constructing the Client:
 *
 *   use Cable8mm\NanoAI\Client;
 *   use Cable8mm\NanoAI\Http\GuzzleHttpClient;
 *
 *   $client = new Client(httpClient: new GuzzleHttpClient());
 *
 * Or with a pre-configured Guzzle client for full control:
 *
 *   $guzzle = new \GuzzleHttp\Client(['timeout' => 60]);
 *   $client = new Client(httpClient: new GuzzleHttpClient($guzzle));
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    private ClientInterface $client;

    /**
     * @param  ClientInterface|null  $client  A pre-configured Guzzle client. If null, a new one is created with the given timeout settings.
     * @param  int|null  $timeoutSeconds  Total request timeout in seconds. Ignored if $client is provided.
     * @param  int|null  $connectTimeoutSeconds  Connection timeout in seconds. Ignored if $client is provided.
     */
    public function __construct(
        ?ClientInterface $client = null,
        private readonly ?int $timeoutSeconds = 30,
        private readonly ?int $connectTimeoutSeconds = 10,
    ) {
        $this->client = $client ?? new Client([
            RequestOptions::TIMEOUT => $this->timeoutSeconds,
            RequestOptions::CONNECT_TIMEOUT => $this->connectTimeoutSeconds,
        ]);
    }

    /**
     * Sends a JSON POST request and returns [statusCode, rawBody].
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     * @return array{0: int, 1: string}
     *
     * @throws NetworkException When Guzzle itself fails (timeout, DNS failure, etc.) or when the payload cannot be JSON-encoded.
     */
    public function post(string $url, array $headers, array $payload): array
    {
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encodedPayload === false) {
            throw new NetworkException('Failed to encode request payload as JSON: '.json_last_error_msg());
        }

        // Ensure Content-Type is set to application/json (matching the cURL HttpClient behavior).
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        try {
            $response = $this->client->post($url, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::BODY => $encodedPayload,
                RequestOptions::HTTP_ERRORS => false, // We read the body ourselves to handle 4xx/5xx.
            ]);
        } catch (GuzzleException $e) {
            throw new NetworkException(
                sprintf('Network request failed (Guzzle): %s', $e->getMessage()),
                0,
                $e,
            );
        }

        return [$response->getStatusCode(), $response->getBody()->getContents()];
    }
}
