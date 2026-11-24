<?php

namespace Pantheon\Terminus\PublicApi;

use GuzzleHttp\Exception\ConnectException;
use Pantheon\Terminus\Request\RetryPolicy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Retry rules for Public API requests, used with GuzzleHttp\Middleware::retry().
 *
 * - 429 and connection failures are retried for every method.
 * - 500/502/503/504 are retried only for idempotent methods (GET, HEAD, OPTIONS, PUT, DELETE).
 *   A POST that hit a 5xx may still have started a workflow, and retrying it could start a second one.
 *   (Request retries every method; this is a deliberate difference.)
 * - Anything else (including 401 and 409) is returned as-is and Client turns it into an exception.
 *
 * Backoff reuses RetryPolicy: linear for 5xx/connect, exponential for 429.
 */
class RetryDecider
{
    private const IDEMPOTENT_METHODS = ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];

    private int $maxRetries;

    private int $backoffSeconds;

    private LoggerInterface $logger;

    /**
     * @param int $maxRetries Capped at 10, like Request.
     * @param int $backoffSeconds Base backoff in seconds. 0 disables waiting (tests).
     */
    public function __construct(int $maxRetries, int $backoffSeconds, LoggerInterface $logger)
    {
        $this->maxRetries = max(0, min(10, $maxRetries));
        $this->backoffSeconds = max(0, $backoffSeconds);
        $this->logger = $logger;
    }

    /**
     * The `decider` callable for Middleware::retry().
     */
    public function shouldRetry(
        int $retries,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?\Throwable $exception = null
    ): bool {
        if ($retries >= $this->maxRetries) {
            return false;
        }

        if ($exception instanceof ConnectException) {
            $reason = $exception->getMessage();
        } elseif ($response !== null && $this->isRetryableResponse($request, $response)) {
            $reason = sprintf('HTTP %d', $response->getStatusCode());
        } else {
            return false;
        }

        $this->logger->warning('Public API {method} {uri} failed ({reason}); retry {n} of {max}.', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()->withQuery(''),
            'reason' => $reason,
            'n' => $retries + 1,
            'max' => $this->maxRetries,
        ]);
        return true;
    }

    /**
     * The `delay` callable for Middleware::retry(): milliseconds before retry number $retries (1-based).
     */
    public function delayMs(int $retries, ?ResponseInterface $response = null): int
    {
        $status = $response !== null ? $response->getStatusCode() : 0;
        return 1000 * RetryPolicy::backoffSeconds($status, $this->backoffSeconds, $retries - 1);
    }

    private function isRetryableResponse(RequestInterface $request, ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();
        if (RetryPolicy::isRateLimit($status)) {
            return true;
        }
        return RetryPolicy::isRetryableStatusCode($status)
            && in_array(strtoupper($request->getMethod()), self::IDEMPOTENT_METHODS, true);
    }
}
