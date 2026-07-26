<?php

declare(strict_types=1);

use Cable8mm\NanoAI\Tests\Support\FakeHttpClient;

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
| Since this is a pure PHP library, Laravel TestCase etc. are not needed.
| Only helper functions for creating "success/failure response fakes" used
| repeatedly in Feature tests are extracted here.
*/

/**
 * FakeHttpClient that returns a success (200) response with the specified text in choices[0].message.content.
 */
function fakeSuccess(string $text): FakeHttpClient
{
    return new FakeHttpClient(200, json_encode([
        'choices' => [['message' => ['content' => $text]]],
    ], JSON_UNESCAPED_UNICODE));
}

/**
 * FakeHttpClient that returns a failure response with the specified status code and error message.
 */
function fakeError(int $statusCode, string $message): FakeHttpClient
{
    return new FakeHttpClient($statusCode, json_encode([
        'error' => ['message' => $message],
    ], JSON_UNESCAPED_UNICODE));
}

uses()->in(__DIR__);
