<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Client;
use Cable8mm\NanoAI\Exception\ApiException;

/**
 * resolveImage() is private and not part of Client's public contract, but it's worth
 * directly verifying that it correctly normalizes URL/data URI/local file inputs.
 */
function resolveImageViaReflection(Client $client, string $image): string
{
    $method = new ReflectionMethod(Client::class, 'resolveImage');
    $method->setAccessible(true);

    return $method->invoke($client, $image);
}

beforeEach(function () {
    $this->client = new Client('openai', 'sk-test', null, [], fakeSuccess('unused'));
});

it('returns http(s) URLs unchanged', function () {
    $resolved = resolveImageViaReflection($this->client, 'https://example.com/cat.jpg');

    expect($resolved)->toBe('https://example.com/cat.jpg');
});

it('returns data URIs unchanged', function () {
    $dataUri = 'data:image/png;base64,aGVsbG8=';

    expect(resolveImageViaReflection($this->client, $dataUri))->toBe($dataUri);
});

it('converts local image file paths to base64 data URIs', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'nano_ai_test_').'.png';
    // 1x1 transparent PNG
    file_put_contents(
        $tmpFile,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
    );

    $resolved = resolveImageViaReflection($this->client, $tmpFile);

    expect($resolved)->toStartWith('data:image/png;base64,');

    unlink($tmpFile);
});

it('throws ApiException for non-existent local file paths', function () {
    resolveImageViaReflection($this->client, '/no/such/file/path.jpg');
})->throws(ApiException::class);
