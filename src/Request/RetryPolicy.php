<?php

namespace Pantheon\Terminus\Request;

/**
 * Class RetryPolicy
 *
 * Default-deny status-code retry policy: only status codes that represent a
 * transient failure belong in RETRYABLE_STATUS_CODES. Adding a code here means
 * "retrying the identical request, unmodified, is expected to eventually
 * succeed." Anything else — including every commonly-seen permanent client or
 * server error — must NOT be added; see the comment in
 * Request::createRetryDecider() for the full rationale per code.
 *
 * @package Pantheon\Terminus\Request
 */
final class RetryPolicy
{
    public const RETRYABLE_STATUS_CODES = [429, 500, 502, 503, 504];

    public const RATE_LIMIT_STATUS_CODE = 429;

    /**
     * @param int $statusCode
     * @return bool
     */
    public static function isRetryableStatusCode(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    public static function isRateLimit(int $statusCode): bool
    {
        return $statusCode === self::RATE_LIMIT_STATUS_CODE;
    }

    /**
     * Number of seconds to wait before the given retry attempt. Rate limits get an
     * exponential backoff, since a fixed/linear delay is unlikely to clear a 429 in time;
     * every other retryable status code keeps the existing linear backoff.
     *
     * @param int $statusCode
     * @param int $retryBackoff Base backoff, in seconds (from the 'http_retry_backoff' config).
     * @param int $retry Zero-indexed retry attempt number.
     * @return int
     */
    public static function backoffSeconds(int $statusCode, int $retryBackoff, int $retry): int
    {
        if (self::isRateLimit($statusCode)) {
            return $retryBackoff * (2 ** ($retry + 1));
        }
        return $retryBackoff * ($retry + 1);
    }
}
