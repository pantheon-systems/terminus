<?php

namespace Pantheon\Terminus\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request as HttpRequest;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class Request
 *
 * Handles requests made by Terminus
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * (the HTTP library Terminus uses). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 *
 * @package Pantheon\Terminus\Request
 */
class Request implements ConfigAwareInterface, ContainerAwareInterface, LoggerAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use SessionAwareTrait;

    const PAGED_REQUEST_ENTRY_LIMIT = 100;
    const HIDDEN_VALUE_REPLACEMENT = '**HIDDEN**';
    const DEBUG_REQUEST_STRING = "#### REQUEST ####\nHeaders: {headers}\nURI: {uri}\nMethod: {method}\nBody: {body}";
    const DEBUG_RESPONSE_STRING =  "#### RESPONSE ####\nHeaders: {headers}\nData: {data}\nStatus Code: {status_code}";

    /**
     * Download file from target URL
     *
     * @param string $url URL to download from
     * @param string $target Target file's name
     * @throws TerminusException
     */
    public function download($url, $target)
    {
        if ($this->getContainer()->get(LocalMachineHelper::class)->getFilesystem()->exists($target)) {
            throw new TerminusException('Target file {target} already exists.', compact('target'));
        }

        $parsed_url = parse_url($url);
        $client = $this->getContainer()->get(Client::class, [[
            'base_uri' => $parsed_url['host'],
            RequestOptions::VERIFY => (boolean)$this->getConfig()->get('verify_host_cert', true),
        ],]);
        $client->request('GET', $url, ['sink' => $target,]);
    }

    /**
     * Make a request to the Dashbord's internal API
     *
     * @param string $path API path (URL)
     * @param array $options Options for the request
     *   string method      GET is default
     *   array form_params  Fed into the body of the request
     *   integer limit      Max number of entries to return
     * @return array
     */
    public function pagedRequest($path, array $options = [])
    {
        $limit = isset($options['limit']) ? $options['limit'] : self::PAGED_REQUEST_ENTRY_LIMIT;

        //$results is an associative array so we don't refetch
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

                //If the last item of the results has previously been received,
                //that means there are no more pages to fetch
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
     * Simplified request method for Pantheon API
     *
     * @param string $path API path (URL)
     * @param array $options Options for the request
     *   string method      GET is default
     *   array form_params  Fed into the body of the request
     * @return array
     */
    public function request($path, array $options = [])
    {
        // Set headers
        $headers = $this->getDefaultHeaders();
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $base_uri = $this->getBaseURI();

        if (strpos($path, '://') === false) {
            $uri = "$base_uri/api/$path";
            if ($session = $this->session()->get('session', false)) {
                $headers['Authorization'] = "Bearer $session";
            }
        } else {
            $uri = $path;
        }

        if (!empty($options['query'])) {
            $uri .= '?' . http_build_query($options['query'], null, '&', PHP_QUERY_RFC3986);
        }

        $body = $debug_body = null;
        if (isset($options['form_params'])) {
            $body = json_encode($options['form_params'], JSON_UNESCAPED_SLASHES);
            $debug_body = $options['form_params'];
        }

        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';

        $client = $this->getContainer()->get(Client::class, [[
           'base_uri' => $base_uri,
           RequestOptions::VERIFY => (boolean)$this->getConfig()->get('verify_host_cert', true),
        ]]);

        $this->logger->debug(
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
        $request = $this->getContainer()->get(HttpRequest::class, [$method, $uri, $headers, $body,]);
        error_reporting(E_ALL);
        $response = $this->sendWithRetry($client, $request);

        return $response;
    }

    /**
     * Send the request using the Guzzle client. Retry the request if a server or network error occurs.
     *
     * @param Client $client
     * @param HttpRequest $request
     * @return array
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    private function sendWithRetry($client, $request)
    {

        $retry_interval = $this->getConfig()->get('http_retry_delay_ms', 100);
        $retry_multiplier = $this->getConfig()->get('http_retry_backoff_multiplier', 2);
        $retry_jitter = $this->getConfig()->get('http_retry_jitter_ms', 100);
        $retry_max = $this->getConfig()->get('http_max_retries', 5);

        $tries = 0;
        while (true) {
            $tries++;

            try {
                $response = $client->send($request);
                $body = json_decode($response->getBody()->getContents());
                $data = [
                  'data' => $body,
                  'headers' => $response->getHeaders(),
                  'status_code' => $response->getStatusCode(),
                ];
                $this->logger->debug(
                    self::DEBUG_RESPONSE_STRING,
                    [
                    'data' => json_encode($this->stripSensitiveInfo((array)$body)),
                    'headers' => json_encode($this->stripSensitiveInfo((array)$data['headers'])),
                    'status_code' => $data['status_code'],
                    ]
                );

                return $data;
            } catch (\Exception $e) {
                // Don't retry on Client errors or redirect loops.
                if ($e instanceof ClientException or $e instanceof TooManyRedirectsException) {
                    throw $e;
                }

                // If we're out of retries then throw an error.
                if ($tries > $retry_max) {
                    throw new TerminusException('HTTPS request failed with error {error}. Maximum retry attempts reached.', ['error' => $e->getMessage()]);
                }

                // For server or connection errors, retry the request until we have reached our maximum retries.
                // Sleep for a specified interval. Jitter is added to prevent clients syncing up accidentally.
                $sleep = $retry_interval + rand(0, $retry_jitter);

                // Increase the retry interval so that we're backing off request to prevent overloading
                $retry_interval = $retry_interval * $retry_multiplier;
                $this->logger->warning('HTTPS request failed with error {error}. Retrying in {sleep} milliseconds..', ['error' => $e->getMessage(), 'sleep' => $sleep]);

                // Sleep the specified number if milliseconds.
                usleep($sleep * 1000);
            }
        }
    }

    /**
     * Parses the base URI for requests
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
     * Gives the default headers for requests
     *
     * @return array
     */
    private function getDefaultHeaders()
    {
        return [
            'Content-type' => 'application/json',
            'User-Agent' => $this->userAgent(),
        ];
    }

    /**
     * @param array
     * @return array
     */
    private function stripSensitiveInfo($data = [])
    {
        if (is_array($data)) {
            $do_not_permit = ['machine_token', 'Authorization', 'session',];
            foreach ($do_not_permit as $verboten) {
                if (isset($data[$verboten])) {
                    $data[$verboten] = self::HIDDEN_VALUE_REPLACEMENT;
                }
            }
        }
        return $data;
    }

    /**
     * Gives the user-agent string
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
}
