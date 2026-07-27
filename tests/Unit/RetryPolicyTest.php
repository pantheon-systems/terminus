<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Request\RetryPolicy;

class RetryPolicyTest extends TestCase
{
    /**
     * @dataProvider retryableStatusCodeProvider
     */
    public function testIsRetryableStatusCode(int $statusCode, bool $expected)
    {
        $this->assertSame($expected, RetryPolicy::isRetryableStatusCode($statusCode));
    }

    public function retryableStatusCodeProvider(): array
    {
        return [
            // Retryable: transient conditions.
            'request timeout' => [408, true],
            'rate limited' => [429, true],
            'internal server error' => [500, true],
            'bad gateway' => [502, true],
            'service unavailable' => [503, true],
            'gateway timeout' => [504, true],

            // Not retryable: success / redirects.
            'ok' => [200, false],
            'im used' => [226, false],
            'multiple choices' => [300, false],
            'moved permanently' => [301, false],

            // Not retryable: permanent client errors.
            'bad request' => [400, false],
            'unauthorized' => [401, false],
            'forbidden' => [403, false],
            'not found' => [404, false],
            'conflict' => [409, false],
            'gone' => [410, false],
            'length required' => [411, false],
            'unprocessable entity' => [422, false],
            'locked' => [423, false],
            'too many header fields' => [431, false],
            'unavailable for legal reasons' => [451, false],

            // Not retryable: permanent server-side failures.
            'not implemented' => [501, false],
            'http version not supported' => [505, false],
            'variant also negotiates' => [506, false],
            'loop detected' => [508, false],
            'network authentication required' => [511, false],
        ];
    }

    public function testIsRateLimit()
    {
        $this->assertTrue(RetryPolicy::isRateLimit(429));

        foreach ([408, 500, 502, 503, 504, 200, 401, 403] as $statusCode) {
            $this->assertFalse(RetryPolicy::isRateLimit($statusCode));
        }
    }

    /**
     * @dataProvider backoffSecondsProvider
     */
    public function testBackoffSeconds(int $statusCode, int $retryBackoff, int $retry, int $expected)
    {
        $this->assertSame($expected, RetryPolicy::backoffSeconds($statusCode, $retryBackoff, $retry));
    }

    public function backoffSecondsProvider(): array
    {
        return [
            // Non-rate-limit codes keep the existing linear backoff: base * (retry + 1).
            'linear, first retry' => [503, 3, 0, 3],
            'linear, second retry' => [503, 3, 1, 6],
            'linear, third retry' => [503, 3, 2, 9],
            'linear, different base' => [500, 5, 3, 20],

            // 429 backs off exponentially: base * 2^(retry + 1).
            'rate limit, first retry' => [429, 3, 0, 6],
            'rate limit, second retry' => [429, 3, 1, 12],
            'rate limit, third retry' => [429, 3, 2, 24],
            'rate limit, different base' => [429, 5, 3, 80],
        ];
    }
}
