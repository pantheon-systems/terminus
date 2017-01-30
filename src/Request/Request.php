<?php

namespace Pantheon\Terminus\Request;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request as HttpRequest;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
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

            $data = $resp['data'];
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

        return ['data' => array_values($results),];
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
        $response = $this->send($path, $options);
        $data = [
            'data' => json_decode($response->getBody()->getContents()),
            'headers' => $response->getHeaders(),
            'status_code' => $response->getStatusCode(),
        ];
        $this->logger->debug("#### RESPONSE ####\nHeaders: {headers}\nData: {data}\nStatus Code: {status_code}", $data);
        return $data;
    }

    /**
     * Sends a request to the API
     *
     * @param string $path API path (URL)
     * @param array $arg_options Request parameters
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function send($path, array $options = [])
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

        $body = isset($options['form_params']) ? json_encode($options['form_params']) : null;

        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';

        $client = $this->getContainer()->get(Client::class, [[
            'base_uri' => $base_uri,
            RequestOptions::VERIFY => (boolean)$this->getConfig()->get('verify_host_cert', true),
        ]]);

        $this->logger->debug(
            "#### REQUEST ####\nHeaders: {headers}\nURI: {uri}\nMethod: {method}\nBody: {body}",
            [
                'headers' => json_encode($headers),
                'uri' => $uri,
                'method' => $method,
                'body' => $body,
            ]
        );

        //Required objects and arrays stir benign warnings.
        error_reporting(E_ALL ^ E_WARNING);
        $request = $this->getContainer()->get(HttpRequest::class, [$method, $uri, $headers, $body]);
        error_reporting(E_ALL);
        $response = $client->send($request);

        return $response;
    }

    /**
     * Parses the base URI for requests
     *
     * @return string
     */
    private function getBaseURI()
    {
        return sprintf(
            '%s://%s:%s',
            $this->getConfig()->get('protocol'),
            $this->getConfig()->get('host'),
            $this->getConfig()->get('port')
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
            'User-Agent' => $this->userAgent(),
            'Content-type' => 'application/json',
        ];
    }

    /**
     * Gives the user-agent string
     *
     * @return string
     */
    private function userAgent()
    {
        return sprintf(
            'Terminus/%s (php_version=%s&script=%s)',
            $this->getConfig()->get('version'),
            $this->getConfig()->get('php_version'),
            $this->getConfig()->get('script')
        );
    }
}
