<?php

/**
 * Base class for Terminus commands
 *
 * @package terminus
 */
abstract class Terminus_Command {

  public $cache;
  public $session;
  public $sites;

  public function __construct() {
    # Load commonly used data from cache.
    $this->cache = Terminus::get_cache();
    $this->session = $this->cache->get_data('session');
    $this->sites = $this->cache->get_data('sites');
  }

  /**
   * Helper code to grab sites and manage local cache.
   */
  public function fetch_sites( $nocache = false ) {
    if (!$this->sites || $nocache) {
      $this->_fetch_sites();
    }
    return $this->sites;
  }

  /**
   * Actually go out and get the sites.
   */
  private function _fetch_sites() {
    Terminus::log( 'Fetching site list from Pantheon' );
    $request = $this->terminus_request( 'user',
                                      $this->session->user_uuid,
                                      'sites',
                                      'GET',
                                      Array('hydrated' => true));
    # TODO: handle errors well.
    $sites = $request['data'];
    $this->cache->put_data( 'sites', $sites );
    $this->sites = $sites;
    return $sites;
  }

  /**
   * Helper function to grab a single site's data from cache if possible.
   */
  public function fetch_site( $site_name, $nocache = false ) {
    if ( $this->_fetch_site($site_name) !== false && !$nocache ) {
      return $this->_fetch_site($site_name);
    }
    # No? Refresh that list.
    $this->_fetch_sites();
    if ( $this->_fetch_site($site_name) !== false ) {
      return $this->_fetch_site($site_name);
    }
    Terminus::error("The site named '$site_name' does not exist. Run `terminus sites show` for a list of sites.");
  }

  /**
   * Private function to deal with our data object for sites and return one
   * by name that includes its uuid.
   */
  private function _fetch_site( $site_name ) {
    foreach ($this->sites as $site_uuid => $data) {
      if ( $data->information->name == $site_name ) {
        $data->information->site_uuid = $site_uuid;
        return $data->information;
      }
    }
    return false;
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
      \Terminus::error("You must login first.");
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
      $host = TERMINUS_HOST;
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
      \Terminus::error('TERMINUS_API_CONNECTION_ERROR', "CONNECTION ERROR: $error");
      return FALSE;
    }

    $info = curl_getinfo($ch);
    if ($info['http_code'] > 399) {
      \Terminus::error('Request failed');
      // Expired session. Really don't like the string comparison.
      if ($info['http_code'] == 403 && $json == '"Session not found."') {
        \Terminus::error('Session expired');
        # Auth_Command->logout();
      }
      return FALSE;
    }

    return array(
      'headers' => $headers_text,
      'json' => $json,
      'data' => json_decode($json)
    );
  }
}

