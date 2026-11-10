<?php

namespace Pantheon\Terminus\PublicApi;

use Closure;
use Consolidation\Config\ConfigInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Builds the Public API Client from Terminus config.
 *
 * Config keys:
 *   public_api_url      Base URL (default https://api.pantheon.io/v1; env TERMINUS_PUBLIC_API_URL).
 *   http_max_retries    Shared with Request (default 5, capped at 10).
 *   http_retry_backoff  Shared with Request (seconds, default 5, minimum 3).
 *   verify_host_cert    Shared with Request.
 *   host_cert           Shared with Request.
 */
class ClientFactory
{
    public const DEFAULT_BASE_URL = 'https://api.pantheon.io/v1';

    /**
     * @param ConfigInterface $config
     * @param Closure $tokenProvider fn(): string — the saved token (personal access token).
     * @param Closure $headersProvider fn(): array — Terminus default headers.
     * @param LoggerInterface $logger
     * @param callable|null $handler Guzzle handler; tests pass a MockHandler.
     * @param int|null $backoffSeconds Overrides http_retry_backoff without the 3s minimum (tests pass 0).
     */
    public static function create(
        ConfigInterface $config,
        Closure $tokenProvider,
        Closure $headersProvider,
        LoggerInterface $logger,
        ?callable $handler = null,
        ?int $backoffSeconds = null
    ): Client {
        $decider = new RetryDecider(
            (int) $config->get('http_max_retries', 5),
            $backoffSeconds ?? max(3, (int) $config->get('http_retry_backoff', 5)),
            $logger
        );

        $stack = HandlerStack::create($handler ?? new StreamHandler());
        $stack->push(Middleware::retry([$decider, 'shouldRetry'], [$decider, 'delayMs']), 'public_api_retry');

        $options = [
            'handler' => $stack,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::VERIFY => (bool) $config->get('verify_host_cert', true),
        ];
        $hostCert = $config->get('host_cert');
        if ($hostCert !== null) {
            $options[RequestOptions::CERT] = $hostCert;
        }

        return new Client(
            new GuzzleClient($options),
            (string) $config->get('public_api_url', self::DEFAULT_BASE_URL),
            $tokenProvider,
            $headersProvider,
            $logger
        );
    }
}
