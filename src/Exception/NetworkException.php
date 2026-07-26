<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Exception;

/**
 * cURL itself failed (timeout, DNS failure, connection refused, etc.).
 * Distinguished from ApiException because the API did not respond at all.
 */
class NetworkException extends NanoAIException {}
