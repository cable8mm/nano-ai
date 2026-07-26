<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Http;

use Cable8mm\NanoAI\Exception\NetworkException;

interface HttpClientInterface
{
    /**
     * JSON POST 요청을 보내고 [statusCode, rawBody]를 반환한다.
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     * @return array{0: int, 1: string}
     *
     * @throws NetworkException cURL 자체가 실패한 경우 (타임아웃, DNS 실패 등)
     */
    public function post(string $url, array $headers, array $payload): array;
}
