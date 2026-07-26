<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Exception;

/**
 * API-level errors excluding authentication/rate-limit (400, 404, 500, etc.)
 * or when the response cannot be parsed as JSON.
 */
class ApiException extends NanoAIException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?string $responseBody = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
