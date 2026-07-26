<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Exception\NetworkException;
use Cable8mm\NanoAI\Http\HttpClient;

it('throws NetworkException for unsupported protocol/format URL', function () {
    $client = new HttpClient(timeoutSeconds: 2, connectTimeoutSeconds: 2);

    $client->post('not-a-valid-url', [], ['x' => 1]);
})->throws(NetworkException::class);

it('throws NetworkException for payload that cannot be JSON-encoded', function () {
    $client = new HttpClient;

    // NAN/INF cannot be represented in JSON, so json_encode() returns false.
    $client->post('http://127.0.0.1:1/unreachable', [], ['bad' => NAN]);
})->throws(NetworkException::class);
