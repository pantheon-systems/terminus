<?php

namespace Pantheon\Terminus\Request;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\RequestOptions;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusUnsupportedSiteException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Helpers\Utility\TraceId;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;

/**
 * Class Request.
 *
 * Handles requests made by Terminus.
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * (the HTTP library Terminus uses). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 *
 * @package Pantheon\Terminus\Request
 */
class Request implements
    ConfigAwareInterface,
    ContainerAwareInterface,
    LoggerAwareInterface,
    SessionAwareInterface,
    IOAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use SessionAwareTrait;
    use IO;

    public const PAGED_REQUEST_ENTRY_LIMIT = 100;

    public const HIDDEN_VALUE_REPLACEMENT = '**HIDDEN**';

    public const DEBUG_REQUEST_STRING = "#### REQUEST ####\n"
        . "Headers: {headers}\n"
        . "URI: {uri}\n"
        . "Method: {method}\n"
        . "Body: {body}";

    public const DEBUG_RESPONSE_STRING = "#### RESPONSE ####\n"
        . "Headers: {headers}\n"
        . "Data: {data}\n"
        . "Status Code: {status_code}";

    public const MAX_HEADER_LENGTH = 4096;

    public const ENVIRONMENT_VARIABLES = [
        'CI',
    ];

    public const UNSUPPORTED_SITE_EXCEPTION_MESSAGE = 'This is not supported for this site.';

    protected ClientInterface $client;

    /**
     * @var array Names of the values to strip from debug output
     */
    protected $sensitive_data = ['machine_token', 'Authorization', 'session',];

    /**
     * Download file from target URL.
     *
     * @param string $url URL to download from
     * @param string $target Target file or directory's name
     * @param bool $overwrite
     *   Overwrite the target file if already exists.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function download($url, $target, bool $overwrite = false)
    {
        if (is_dir($target)) {
            if (substr($target, -1) == DIRECTORY_SEPARATOR) {
                $target = $target . strtok(basename($url), '?');
            } else {
                $target = $target . DIRECTORY_SEPARATOR . strtok(
                    basename($url),
                    '?'
                );
            }
        }
        $this->logger->notice('Downloading {url} to {target}', [
            'url' => strtok(basename($url), '?'),
            'target' => $target,
        ]);

        if (
            !$overwrite && $this->getContainer()
                ->get(LocalMachineHelper::class)
                ->getFilesystem()
                ->exists($target)
        ) {
            throw new TerminusException(
                'Target file {target} already exists.',
                compact('target')
            );
        }

        $parsed_url = parse_url($url);
        $this->getClient($parsed_url['host'])->request(
            'GET',
            $url,
            ['sink' => $target]
        );
    }

    /**
     * Returns a configured Client object.
     *
     * @param string $base_uri Defaults to the getBaseURI() value
     */
    private function getClient($base_uri = null): ClientInterface
    {
        if (!isset($this->client)) {
            $config = $this->getConfig();
            $stack = HandlerStack::create(new StreamHandler());
            $stack->push(Middleware::retry($this->createRetryDecider()));

            $params = $config->get('client_options') + [
                    'base_uri' => ($base_uri === null) ? $this->getBaseURI(
                    ) : $base_uri,
                    RequestOptions::VERIFY => (bool)$config->get(
                        'verify_host_cert',
                        true
                    ),
                    'handler' => $stack,
                ];

            $host_cert = $config->get('host_cert');
            if ($host_cert !== null) {
                $params[RequestOptions::CERT] = $host_cert;
            }

            $this->client = new Client($params);
        }
        return $this->client;
    }

    /**
     * Wraps the global sleep() so tests can override retry delays.
     *
     * @param int $seconds
     */
    protected function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * Returns the Retry Decider middleware.
     *
     * @return callable
     */
    private function createRetryDecider(): callable
    {
        $config = $this->getConfig();
        $maxRetries = $config->get('http_max_retries', 5);
        // Cap max retries at 10.
        $maxRetries = $maxRetries > 10 ? 10 : $maxRetries;
        $retryBackoff = $config->get('http_retry_backoff', 5);
        // Retry backoff should be at least 3.
        $retryBackoff = $retryBackoff < 3 ? 3 : $retryBackoff;
        $logger = $this->logger;
        $logWarning = function (string $message) use ($logger) {
            if ($this->output()->isVerbose()) {
                $logger->warning($message);
            }
        };

        return function (
            $retry,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?Exception $exception = null
        ) use (
            $maxRetries,
            $logWarning,
            $retryBackoff
        ) {
            $logWarningOnRetry = fn (string $reason) => 0 === $retry
                ? $logWarning(
                    sprintf(
                        'HTTP request %s %s has failed: %s',
                        $request->getMethod(),
                        $request->getUri(),
                        $reason
                    )
                )
                : $logWarning(
                    sprintf(
                        'Retrying %s %s %s out of %s (reason: %s)',
                        $request->getMethod(),
                        $request->getUri(),
                        $retry,
                        $maxRetries,
                        $reason
                    )
                );

            if ($exception instanceof ConnectException) {
                // Retry on connection-related exceptions such as "Connection refused" and "Operation timed out".
                if ($retry !== $maxRetries) {
                    $logWarningOnRetry($exception->getMessage());
                    $logWarning(sprintf("Retrying in %s seconds.", $retryBackoff * ($retry + 1)));
                    $this->sleep($retryBackoff * ($retry + 1));

                    return true;
                }
            } elseif (null !== $exception) {
                throw new TerminusException(
                    'HTTPS request has failed with error "{error}".',
                    ['error' => $exception->getMessage()]
                );
            } else {
                $statusCode = $response->getStatusCode();

                if (!RetryPolicy::isRetryableStatusCode($statusCode)) {
                    // Non-retryable response. This includes:
                    //   - 2xx success (nothing to retry) and 3xx redirects (Guzzle normally
                    //     follows these itself; retrying one is meaningless).
                    //   - 400, 404, 405, 406      - permanent client errors (bad request, not
                    //                               found, method not allowed, not acceptable).
                    //   - 408 Request Timeout     - historically has not been retried; some operations
                    //                               might not be idempotent and therefore not safe
                    //                               to blindly retry.
                    //   - 401 Unauthorized        - Terminus refreshes the session proactively before
                    //                               every command (Authorizer::ensureLogin()) and
                    //                               reactively on a mid-command 401 (Request::request()'s
                    //                               refresh-and-retry-once, see refreshSession()); a 401
                    //                               that survives both means credentials are invalid or
                    //                               revoked, and retrying again cannot fix that.
                    //   - 403 Forbidden           - permanent authorization denial, never retried.
                    //   - 409 Conflict            - handled specially below (may throw
                    //                               TerminusUnsupportedSiteException).
                    //   - 410-431, 451            - permanent client errors (Gone, Payload Too
                    //                               Large, Unprocessable Entity, Locked, legal
                    //                               block, etc.) — retrying won't change them.
                    //   - 501, 505-511            - permanent server-side/config failures.
                    // See RetryPolicy::RETRYABLE_STATUS_CODES for the transient codes that ARE
                    // retried below (429, 500, 502, 503, 504).
                    return false;
                }

                if ($retry !== $maxRetries) {
                    $reason = RetryPolicy::isRateLimit($statusCode)
                        ? 'rate limit exceeded (HTTP 429 Too Many Requests)'
                        : sprintf('status code - %s', $statusCode);
                    // Rate limits back off exponentially; other retryable codes keep the
                    // existing linear backoff. See RetryPolicy::backoffSeconds().
                    $delay = RetryPolicy::backoffSeconds($statusCode, $retryBackoff, $retry);

                    $logWarningOnRetry($reason);
                    $this->logger->debug(
                        'Response body: {body}',
                        ['body' => $response->getBody()->getContents()]
                    );

                    $logWarning(sprintf("Retrying in %s seconds.", $delay));
                    $this->sleep($delay);

                    return true;
                }
            }

            // Response can be null if there is a network disconnect.  Get a different error message in that case.
            if (is_object($response) && is_object($response->getBody()) && $response->getBody()->getContents() !== '') {
                $error = $response->getBody()->getContents();
            } elseif (null !== $exception && '' != $exception->getMessage()) {
                $error = $exception->getMessage();
            } else {
                $error = "Undefined";
            }

            $this->logger->error(
                "HTTP request {method} {uri} has failed with error {error}.",
                [
                    'method' => $request->getMethod(),
                    'uri' => $request->getUri(),
                    'error' => $error,
                ]
            );

            throw new TerminusException(
                'HTTP request has failed with error "Maximum retry attempts reached".',
            );
        };
    }

    /**
     * Parses the base URI for requests.
     *
     * @return string
     */
    private function getBaseURI()
    {
        $config = $this->getConfig();
        return sprintf(
            '%s://%s:%s',
            $config->get('protocol'),
            $config->get('host'),
            $config->get('port')
        );
    }

    /**
     * Make a request to the Dashboard's internal API.
     *
     * @param string $path API path (URL)
     * @param array $options Options for the request
     *   string method      GET is default
     *   array form_params  Fed into the body of the request
     *   integer limit      Max number of entries to return
     *
     * @return array
     *
     * @throws GuzzleException
     * @throws TerminusException
     */
    public function pagedRequest($path, array $options = [])
    {
        $limit = $options['limit'] ?? self::PAGED_REQUEST_ENTRY_LIMIT;

        // $results is an associative array, so we don't re-fetch.
        $results = [];
        $finished = false;
        $start = null;

        while (!$finished) {
            $paged_path = $path . '?limit=' . $limit;
            if ($start) {
                $paged_path .= '&start=' . $start;
            }

            $resp = $this->request($paged_path);

            $data = (array)$resp['data'];
            if (count($data) > 0) {
                $start = end($data)->id;

                // If the last item of the results has previously been received,
                // that means there are no more pages to fetch.
                if (isset($results[$start])) {
                    $finished = true;
                    continue;
                }

                foreach ($data as $item) {
                    $results[$item->id] = $item;
                }
            } else {
                $finished = true;
            }
        }

        return ['data' => $results,];
    }

    /**
     * Simplified request method for Pantheon API. On a 401 Unauthorized response,
     * attempts to refresh the session once and retries the request a single time.
     *
     * @param string $path API path (URL)
     * @param array $options Options for the request
     *   string method      GET is default
     *   array form_params  Fed into the body of the request
     *
     * @return RequestOperationResult
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws TerminusException
     */
    public function request($path, array $options = []): RequestOperationResult
    {
        $result = $this->requestWithoutRefreshHandling($path, $options);

        if ($result->getStatusCode() === 401 && $this->refreshSession()) {
            $result = $this->requestWithoutRefreshHandling($path, $options);
        }

        return $result;
    }

    /**
     * Attempts to refresh the current session by re-authenticating with the saved
     * machine token. Used to recover from a session that expired mid-command.
     *
     * @return bool True if the session was refreshed successfully.
     */
    private function refreshSession(): bool
    {
        try {
            $this->session()->getAuthToken()->logIn();
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Session refresh failed after receiving a 401: {message}',
                ['message' => $e->getMessage()]
            );
            return false;
        }
    }

    /**
     * Sends a request to the Pantheon API without any 401 refresh-and-retry handling.
     * Used directly by SavedToken::logIn(), since that call IS the refresh operation
     * and must never trigger a nested refresh attempt.
     *
     * @param string $path API path (URL)
     * @param array $options Options for the request
     *   string method      GET is default
     *   array form_params  Fed into the body of the request
     *
     * @return RequestOperationResult
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws TerminusException
     */
    public function requestWithoutRefreshHandling($path, array $options = []): RequestOperationResult
    {
        $config = $this->getConfig();

        // Set headers.
        $parts = explode('/', $path);
        $part = array_pop($parts);
        $headers = $this->getDefaultHeaders();
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        if (strpos($path ?? '', '://') === false) {
            $uri = "{$this->getBaseURI()}/api/$path";
            if ($part !== 'machine-token') {
                $headers['Authorization'] = sprintf(
                    'Bearer %s',
                    $this->session()->get('session')
                );
            }
        } else {
            $uri = $path;
        }
        $body = $debug_body = null;
        if (isset($options['form_params'])) {
            $debug_body = $this->stripSensitiveInfo($options['form_params']);
            $body = json_encode(
                $options['form_params'],
                JSON_UNESCAPED_SLASHES
            );
            unset($options['form_params']);
            $headers['Content-Type'] = 'application/json';
            $headers['Content-Length'] = strlen($body);
        }

        $auth_cookie_key = $config->get('auth_cookie_key');
        if ($auth_cookie_key) {
            $this->sensitive_data[] = 'Cookie';
            $headers = [
                'Cookie' => "$auth_cookie_key={$this->session()->get('session')}",
            ];
            if (isset($options['headers'])) {
                $headers = array_merge($headers, $options['headers']);
            }
            $options['headers'] = $headers;
        }

        $method = isset($options['method']) ? strtoupper(
            $options['method'] ?? ''
        ) : 'GET';
        $this->logger->info(
            self::DEBUG_REQUEST_STRING,
            [
                'headers' => json_encode(
                    $this->stripSensitiveInfo($headers),
                    JSON_UNESCAPED_SLASHES
                ),
                'uri' => $uri,
                'method' => $method,
                'body' => json_encode(
                    $this->stripSensitiveInfo($debug_body),
                    JSON_UNESCAPED_SLASHES
                ),
            ]
        );
        //Required objects and arrays stir benign warnings.
        error_reporting(E_ALL ^ E_WARNING);
        $response = $this->getClient()->send(
            new \GuzzleHttp\Psr7\Request(
                $method,
                $uri,
                $headers,
                $body
            ),
            $options
        );
        $body = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();
        $decoded_body = null;

        // Don't attempt to decode JSON if the body is empty.
        if (!empty($body)) {
            try {
                $decoded_body = \json_decode(
                    $body,
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $jsonException) {
                $this->logger->debug('json_decode exception: {message}', [
                    'message' => $jsonException->getMessage()
                ]);
            }
        }

        if ($response->getStatusCode() == 409) {
            if (!empty($decoded_body) && !empty($decoded_body->message)) {
                // This request is expected to fail for an unsupported site, throw exception.
                throw new TerminusUnsupportedSiteException($decoded_body->message);
            } elseif (!empty($decoded_body) && !empty($decoded_body->reason)) {
                // This request is expected to fail, use generic reason.
                throw new TerminusUnsupportedSiteException(
                    self::UNSUPPORTED_SITE_EXCEPTION_MESSAGE
                );
            }
        }

        return new RequestOperationResult([
            'data' => $decoded_body ?? $body,
            'headers' => $headers,
            'status_code' => $statusCode,
            'status_code_reason' => $response->getReasonPhrase(),
        ]);
    }

    /**
     * Gives the default headers for requests.
     *
     * @return array
     */
    private function getDefaultHeaders()
    {
        return [
            'User-Agent' => $this->userAgent(),
            'Accept' => 'application/json',
            'X-Pantheon-Trace-Id' => TraceId::getTraceId(),
            'X-Pantheon-Terminus-Command' => $this->terminusCommand(),
            'X-Pantheon-Terminus-Environment' => $this->terminusEnvironment(),
        ];
    }

    /**
     * Gives the user-agent string.
     *
     * @return string
     */
    private function userAgent()
    {
        $config = $this->getConfig();
        return sprintf(
            'Terminus/%s (php_version=%s&script=%s)',
            $config->get('version'),
            $config->get('php_version'),
            $config->get('script')
        );
    }

    /**
     * Gives the terminus command as json, truncated if necessary.
     *
     * @return string
     */
    private function terminusCommand()
    {
        $input = $this->getContainer()->get('input');
        $candidate = json_encode([
            'command' => $input->getFirstArgument(),
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions(),
            'truncated' => false,
        ]);

        if (strlen($candidate) > self::MAX_HEADER_LENGTH) {
            return json_encode([
                'command' => $input->getFirstArgument(),
                'truncated' => true,
            ]);
        }

        return $candidate;
    }

    /**
     * Returns terminus execution environment variables as json.
     */
    private function terminusEnvironment()
    {
        $values = [];
        foreach (self::ENVIRONMENT_VARIABLES as $var) {
            $values[$var] = $_SERVER[$var] ?? false;
        }
        $values['OS'] = PHP_OS;
        return json_encode($values);
    }

    /**
     * Execute multiple requests concurrently using Guzzle promises.
     *
     * @param array $requests Array of request specifications:
     *   [
     *     'key' => ['path' => 'api/path', 'options' => [...]],
     *     ...
     *   ]
     * @param array $options Global options:
     *   - 'continue_on_error' => bool (default: true) - Continue if some requests fail
     *   - 'concurrency' => int (default: 10) - Max concurrent requests
     *
     * @return array Array of results keyed by request key:
     *   [
     *     'key' => [
     *       'success' => true|false,
     *       'result' => RequestOperationResult (if success),
     *       'error' => Exception (if failure)
     *     ],
     *     ...
     *   ]
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestConcurrent(array $requests, array $options = []): array
    {
        // Default options
        $defaults = [
            'continue_on_error' => true,
            'concurrency' => $this->getConfig()->get('concurrent_max_concurrency', 10),
        ];
        $options = array_merge($defaults, $options);

        // Check if concurrent enabled
        if (!$this->getConfig()->get('concurrent_requests_enabled', true)) {
            return $this->requestSequential($requests);
        }

        $client = $this->getAsyncClient();
        $promises = [];

        // Build promises for all requests
        foreach ($requests as $key => $request_spec) {
            $path = $request_spec['path'];
            $req_options = $request_spec['options'] ?? [];

            // Build PSR-7 request
            $psr7Request = $this->buildPsr7Request($path, $req_options);

            // Create async promise
            $promises[$key] = $client->sendAsync($psr7Request, $req_options);
        }

        // Execute all promises concurrently
        $this->logger->info('Executing {count} concurrent requests', ['count' => count($promises)]);
        $settled = PromiseUtils::settle($promises)->wait();

        // Transform results
        return $this->transformSettledResults($settled, $options);
    }

    /**
     * Get async-capable Guzzle client.
     *
     * @param string $base_uri Optional base URI override
     * @return ClientInterface
     */
    private function getAsyncClient($base_uri = null): ClientInterface
    {
        $config = $this->getConfig();

        // Use CurlMultiHandler for async (or StreamHandler as fallback)
        if (extension_loaded('curl')) {
            $handler = new \GuzzleHttp\Handler\CurlMultiHandler();
        } else {
            $handler = new StreamHandler();
        }

        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::retry($this->createRetryDecider()));

        $params = $config->get('client_options') + [
            'base_uri' => ($base_uri === null) ? $this->getBaseURI() : $base_uri,
            RequestOptions::VERIFY => (bool)$config->get('verify_host_cert', true),
            'handler' => $stack,
            'timeout' => $config->get('concurrent_timeout', 30),
        ];

        $host_cert = $config->get('host_cert');
        if ($host_cert !== null) {
            $params[RequestOptions::CERT] = $host_cert;
        }

        return new Client($params);
    }

    /**
     * Build PSR-7 request from path and options.
     *
     * @param string $path API path
     * @param array $options Request options
     * @return RequestInterface
     */
    private function buildPsr7Request(string $path, array $options = []): RequestInterface
    {
        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';
        $headers = $this->getDefaultHeaders();

        // Merge custom headers
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        // Build URI
        if (strpos($path ?? '', '://') === false) {
            $uri = "{$this->getBaseURI()}/api/$path";
            // Add authorization for non-machine-token requests
            $parts = explode('/', $path);
            $part = array_pop($parts);
            if ($part !== 'machine-token') {
                $headers['Authorization'] = sprintf(
                    'Bearer %s',
                    $this->session()->get('session')
                );
            }
        } else {
            $uri = $path;
        }

        // Build body
        $body = null;
        if (isset($options['form_params'])) {
            $body = json_encode($options['form_params'], JSON_UNESCAPED_SLASHES);
            $headers['Content-Type'] = 'application/json';
            $headers['Content-Length'] = strlen($body);
        }

        return new \GuzzleHttp\Psr7\Request($method, $uri, $headers, $body);
    }

    /**
     * Transform settled promise results into standardized format.
     *
     * @param array $settled Settled promise results from Utils::settle()
     * @param array $options Options (continue_on_error, etc.)
     * @return array Transformed results
     */
    private function transformSettledResults(array $settled, array $options): array
    {
        $results = [];
        $successes = 0;
        $failures = 0;

        foreach ($settled as $key => $result) {
            if ($result['state'] === 'fulfilled') {
                $response = $result['value'];
                $results[$key] = [
                    'success' => true,
                    'result' => new RequestOperationResult([
                        'data' => $this->decodeResponse($response),
                        'headers' => $response->getHeaders(),
                        'status_code' => $response->getStatusCode(),
                        'status_code_reason' => $response->getReasonPhrase(),
                    ]),
                    'error' => null,
                ];
                $successes++;
            } else {
                $exception = $result['reason'];
                $results[$key] = [
                    'success' => false,
                    'result' => null,
                    'error' => $exception,
                ];
                $failures++;

                $this->logger->warning('Concurrent request failed for {key}: {error}', [
                    'key' => $key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->logger->info('Concurrent requests complete: {successes} succeeded, {failures} failed', [
            'successes' => $successes,
            'failures' => $failures,
        ]);

        return $results;
    }

    /**
     * Decode response body into object/array.
     *
     * @param ResponseInterface $response HTTP response
     * @return mixed Decoded JSON object, or raw body string
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();
        if (empty($body)) {
            return null;
        }

        try {
            return \json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->debug('JSON decode failed: {message}', ['message' => $e->getMessage()]);
            return $body;
        }
    }

    /**
     * Fallback: execute requests sequentially (when concurrent disabled).
     *
     * @param array $requests Array of request specifications
     * @return array Results in same format as requestConcurrent()
     */
    private function requestSequential(array $requests): array
    {
        $results = [];
        foreach ($requests as $key => $request_spec) {
            try {
                $result = $this->request($request_spec['path'], $request_spec['options'] ?? []);
                $results[$key] = [
                    'success' => true,
                    'result' => $result,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'result' => null,
                    'error' => $e,
                ];
            }
        }
        return $results;
    }

    /**
     * Removes sensitive information.
     *
     * @param array
     *
     * @return array
     */
    private function stripSensitiveInfo($data = [])
    {
        if (is_array($data)) {
            foreach ($this->sensitive_data as $key) {
                if (isset($data[$key])) {
                    $data[$key] = self::HIDDEN_VALUE_REPLACEMENT;
                }
            }
        }
        return $data;
    }
}
