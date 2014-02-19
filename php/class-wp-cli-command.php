<?php

/**
 * Base class for WP-CLI commands
 *
 * @package terminus
 */
abstract class WP_CLI_Command {

  public $cache;
  public $session;

	public function __construct() {
	  $this->cache = WP_CLI::get_cache();
	  $this->session = $this->cache->get_data('session');
	}

  /**
   * Make a request to the Dashbord's internal API.
   *
   * @param $realm
   *    Permissions realm for data request: currently "user" or "site" but in the
   *    future this could also be "organization" or another high-level business
   *    object (e.g. "product" for managing your app). Can also be "public" to
   *    simply pull read-only data that is not privileged.
   *
   * @param $uuid
   *    The UUID of the item in the realm you want to access.
   *
   * @param $method
   *    HTTP method (verb) to use.
   *
   * @param $data
   *    A native PHP data structure (int, string, arary or simple object) to be
   *    sent along with the request. Will be encoded as JSON for you.
   */
  public function terminus_request($realm, $uuid, $path = FALSE, $method = 'GET', $data = NULL) {
    if ($this->session == FALSE) {
      \WP_CLI::error("You must login first.");
      exit;
    }
    static $ch = FALSE;
    if (!$ch) {
      $ch = curl_init();
    }
    $headers = array();
    $host = TERMINUS_HOST;
    if (strpos(TERMINUS_HOST, 'onebox') !== FALSE) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      $host = 'onebox.getpantheon.com';
    }
    $url = 'https://'. $host . '/terminus.php?' . $realm . '=' . $uuid;
    if ($path) {
      $url .= '&path='. urlencode($path);
    }
    if ($data) {
      // The $data for POSTs, PUTs, DELETEs are sent as JSON.
      if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        $data = json_encode(array('data' => $data));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
        array_push($headers, 'Content-Type: application/json', 'Content-Length: ' . strlen($data));
      }
      // $data for GETs is sent as querystrings.
      else if ($method === 'GET') {
        $url .= '?' . http_build_query($data);
      }
    }
    // Set URL and other appropriate options.
    $opts = array(
      CURLOPT_URL => $url,
      CURLOPT_HEADER => 1,
      CURLOPT_PORT => TERMINUS_PORT,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_COOKIE => $this->session->session,
      CURLOPT_HTTPHEADER => $headers,
    );
    curl_setopt_array($ch, $opts);

    $result = curl_exec($ch);
    list($headers_text, $json) = explode("\r\n\r\n", $result, 2);
    // Work around extra 100 Continue headers - http://stackoverflow.com/a/2964710/1895669
    if (strpos($headers_text," 100 Continue") !== FALSE) {
      list($headers_text, $json) = explode("\r\n\r\n", $json , 2);
    }

    if (curl_errno($ch) != 0) {
      $error = curl_error($ch);
      curl_close($ch);
      \WP_CLI::error('TERMINUS_API_CONNECTION_ERROR', "CONNECTION ERROR: $error");
      return FALSE;
    }

    $info = curl_getinfo($ch);
    if ($info['http_code'] > 399) {
      \WP_CLI::error('Request failed');
      // Expired session. Really don't like the string comparison.
      if ($info['http_code'] == 403 && $json == '"Session not found."') {
        \WP_CLI::error('Session expired');
        # Auth_Command->logout();
      }
      return FALSE;
    }

    return array('headers' => $headers_text, 'json' => $json);
  }
}

