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
use GuzzleHttp\RequestOptions;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
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

    const PAGED_REQUEST_ENTRY_LIMIT = 100;

    const HIDDEN_VALUE_REPLACEMENT = '**HIDDEN**';

    const DEBUG_REQUEST_STRING = "#### REQUEST ####\nHeaders: {headers}\nURI: {uri}\nMethod: {method}\nBody: {body}";

    const DEBUG_RESPONSE_STRING = "#### RESPONSE ####\nHeaders: {headers}\nData: {data}\nStatus Code: {status_code}";

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
                $target = $target . DIRECTORY_SEPARATOR . strtok(basename($url), '?');
            }
        }
        $this->logger->notice('Downloading {url} to {target}', [
            'url' => strtok(basename($url), '?'),
            'target' => $target,
        ]);

        if (!$overwrite && $this->getContainer()->get(LocalMachineHelper::class)->getFilesystem()->exists($target)) {
            throw new TerminusException('Target file {target} already exists.', compact('target'));
        }

        $parsed_url = parse_url($url);
        $this->getClient($parsed_url['host'])->request('GET', $url, ['sink' => $target]);
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
                    'base_uri' => ($base_uri === null) ? $this->getBaseURI() : $base_uri,
                    RequestOptions::VERIFY => (boolean) $config->get('verify_host_cert', true),
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
     * Returns the Retry Decider middleware.
     *
     * @return callable
     */
    private function createRetryDecider(): callable
    {
        $config = $this->getConfig();
        $maxRetries = $config->get('http_max_retries', 5);
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
            $logWarning
        ) {
            $logWarningOnRetry = fn (string $reason) => 0 === $retry
                ? $logWarning(sprintf(
                    'HTTP request %s %s has failed: %s',
                    $request->getMethod(),
                    $request->getUri(),
                    $reason
                ))
                : $logWarning(sprintf(
                    'Retrying %s %s %s out of %s (reason: %s)',
                    $request->getMethod(),
                    $request->getUri(),
                    $retry,
                    $maxRetries,
                    $reason
                ));

            if ($exception instanceof ConnectException) {
                // Retry on connection-related exceptions such as "Connection refused" and "Operation timed out".
                if ($retry !== $maxRetries) {
                    $logWarningOnRetry($exception->getMessage());

                    return true;
                }
            } elseif (null !== $exception) {
                throw new TerminusException(
                    'HTTPS request has failed with error "{error}".',
                    ['error' => $exception->getMessage()]
                );
            } else {
                if (preg_match('/[2,4]0\d/', $response->getStatusCode())) {
                    // Do not retry on 20x and 40x responses.
                    return false;
                }

                if ($retry !== $maxRetries) {
                    $logWarningOnRetry(sprintf('status code - %s', $response->getStatusCode()));

                    return true;
                }
            }

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
                if (count($data) < $limit) {
                    $finished = true;
                }
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
     * Simplified request method for Pantheon API.
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
    public function request($path, array $options = []) : RequestOperationResult
    {
        // Set headers.
        $parts = explode('/', $path);
        $part = array_pop($parts);
        $headers = $this->getDefaultHeaders();
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        if (strpos($path, '://') === false) {
            $uri = "{$this->getBaseURI()}/api/$path";
            if ($part !== 'machine-token') {
                $headers['Authorization'] = sprintf('Bearer %s', $this->session()->get('session'));
            }
        } else {
            $uri = $path;
        }
        $body = $debug_body = null;
        if (isset($options['form_params'])) {
            $debug_body = $this->stripSensitiveInfo($options['form_params']);
            $body = json_encode($options['form_params'], JSON_UNESCAPED_SLASHES);
            unset($options['form_params']);
            $headers['Content-Type'] = 'application/json';
            $headers['Content-Length'] = strlen($body);
        }

        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';
        $this->logger->info(
            self::DEBUG_REQUEST_STRING,
            [
                'headers' => json_encode($this->stripSensitiveInfo($headers), JSON_UNESCAPED_SLASHES),
                'uri' => $uri,
                'method' => $method,
                'body' => json_encode($this->stripSensitiveInfo($debug_body), JSON_UNESCAPED_SLASHES),
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
        try {
            $body = \json_decode(
                $body,
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $jsonException) {
            $this->logger->debug($jsonException->getMessage());
        }

        return new RequestOperationResult([
            'data' => $body,
            'headers' => $response->getHeaders(),
            'status_code' => $response->getStatusCode(),
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
