<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Exception;

/**
 * Request rate limit exceeded (HTTP 429). Commonly encountered when testing free/low-cost models.
 */
class RateLimitException extends NanoAIException {}
