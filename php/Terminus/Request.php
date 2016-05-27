<?php

namespace Terminus;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Terminus\Exceptions\TerminusException;
use Terminus\Runner;
use Terminus\Utils;

/**
 * Handles requests made by terminus
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * (the HTTP library Terminus uses). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 */

class Request {

  /**
   * Download file from target URL
   *
   * @param string $url    URL to download from
   * @param string $target Target file's name
   * @return bool True if download succeeded
   * @throws TerminusException
   */
  public static function download($url, $target) {
    if (file_exists($target)) {
      throw new TerminusException(
        'Target file {target} already exists.',
        compact('target')
      );
    }

    $client   = new Client();
    $response = $client->request('GET', $url, ['sink' => $target,]);
    return true;
  }

  /**
   * Make a request to the Dashbord's internal API
   *
   * @param string $path    API path (URL)
   * @param array  $options Options for the request
   *   [string] method GET is default
   *   [mixed]  data   Native PHP data structure (e.g. int, string array, or
   *     simple object) to be sent along with the request. Will be encoded as
   *     JSON for you.
   * @return array
   */
  public function pagedRequest($path, array $options = []) {
    $limit = 100;
    if (isset($options['limit'])) {
      $limit = $options['limit'];
    }

    //$results is an associative array so we don't refetch
    $results  = [];
    $finished = false;
    $start    = null;

    while (!$finished) {
      $paged_path = $path . '?limit=' . $limit;
      if ($start) {
        $paged_path .= '&start=' . $start;
      }

      $resp = $this->request($paged_path);

      $data = $resp['data'];
      if (count($data) > 0) {
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

    $return = ['data' => array_values($results),];
    return $return;
  }

  /**
   * Simplified request method for Pantheon API
   *
   * @param string $path        API path (URL)
   * @param array  $arg_options Options for the request
   *   [string] method        GET is default
   *   [mixed]  data          Native PHP data structure (e.g. int, string
   *     array, or simple object) to be sent along with the request. Will
   *     be encoded as JSON for you.
   *   [boolean] absolute_url True if URL passed is to be treated as absolute
   * @return array
   * @throws TerminusException
   */
  public function request($path, $arg_options = []) {
    $default_options = [
      'method'       => 'get',
      'absolute_url' => false,
    ];
    $options = array_merge($default_options, $arg_options);

    $url = $path;
    if ((strpos($path, 'http') !== 0) && !$options['absolute_url']) {
      $url = sprintf(
        '%s://%s:%s/api/%s',
        TERMINUS_PROTOCOL,
        TERMINUS_HOST,
        TERMINUS_PORT,
        $path
      );
    }

    $response = $this->send($url, $options['method'], $options);

    $data = [
      'data'        => json_decode($response->getBody()->getContents()),
      'headers'     => $response->getHeaders(),
      'status_code' => $response->getStatusCode(),
    ];
    return $data;
  }

  /**
   * Sends a request to the API
   *
   * @param string $uri        URL for API request
   * @param string $method     Request method (i.e. PUT, POST, DELETE, or GET)
   * @param array  $arg_params Request parameters
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function send($uri, $method, array $arg_params = []) {
    $extra_params = [
      'headers'         => [
        'User-Agent'    => $this->userAgent(),
        'Content-type'  => 'application/json',
      ],
      RequestOptions::VERIFY => (strpos(TERMINUS_HOST, 'onebox') === false),
    ];

    if ((!isset($arg_params['absolute_url']) || !$arg_params['absolute_url'])
      && $session = Session::instance()->get('session', false)
    ) {
      $extra_params['headers']['Authorization'] = "Bearer $session";
    }
    $params = array_merge_recursive($extra_params, $arg_params);
    if (isset($params['form_params'])) {
      $params['json'] = $params['form_params'];
      unset($params['form_params']);
    }
    $params[RequestOptions::VERIFY] = (strpos(TERMINUS_HOST, 'onebox') === false);

    $client = new Client(
      [
        'base_uri' => $this->getBaseUri(),
        'cookies'  => $this->fillCookieJar($params),
      ]
    );
    unset($params['cookies']);

    Runner::getLogger()->debug(
      "#### REQUEST ####\nParams: {params}\nURI: {uri}\nMethod: {method}",
      [
        'params' => json_encode($params),
        'uri'    => $uri,
        'method' => $method,
      ]
    );

    //Required objects and arrays stir benign warnings.
    error_reporting(E_ALL ^ E_WARNING);
    $request = new HttpRequest(ucwords($method), $uri, $params);
    error_reporting(E_ALL);
    $response = $client->send($request, $params);

    return $response;
  }

  /**
   * Sets up and fills a cookie jar
   *
   * @param array $params Request data to fill jar with
   * @return \GuzzleHttp\Cookie\CookieJar $jar
   */
  private function fillCookieJar(array $params) {
    $jar     = new CookieJar();
    $cookies = [];
    if (isset($params['cookies'])) {
      $cookies = array_merge($cookies, $params['cookies']);
    }
    $jar->fromArray($cookies, '');
    return $jar;
  }

  /**
   * Parses the base URI for requests
   *
   * @return string
   */
  private function getBaseUri() {
    $base_uri = sprintf(
      '%s://%s:%s',
      TERMINUS_PROTOCOL,
      TERMINUS_HOST,
      TERMINUS_PORT
    );
    return $base_uri;
  }

  /**
   * Gives the user-agent string
   *
   * @return string
   */
  private function userAgent() {
    $agent = sprintf(
      'Terminus/%s (php_version=%s&script=%s)',
      constant('TERMINUS_VERSION'),
      phpversion(),
      constant('TERMINUS_SCRIPT')
    );
    return $agent;
  }

}
