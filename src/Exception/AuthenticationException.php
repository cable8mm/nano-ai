<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Exception;

/**
 * API Key is missing, invalid, or expired (HTTP 401 / 403).
 */
class AuthenticationException extends NanoAIException {}
