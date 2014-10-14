<?php
use \Terminus\Endpoint;
use \Terminus\Request;
use \Terminus\Fixtures;

/**
 * Base class for Terminus commands
 *
 * @package terminus
 */
abstract class Terminus_Command {

  public $cache;
  public $session;
  public $sites;

  protected $_func;
  protected $_siteInfo;
  protected $_bindings;

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
                                      @$this->session->user_uuid,
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

    if ( defined("CLI_TEST_MODE") || 1 === getenv("USE_FIXTURES") ) {
      return $this->fixtured_request();
    }

    if ($this->session == FALSE) {
      \Terminus::error("You must login first.");
      exit;
    }
    try {
      $url = Endpoint::get( array( 'realm' => $realm, 'uuid'=>$uuid, 'path'=>$path ) );
      $resp = Request::send( $url, $method, array('cookies'=> array('X-Pantheon-Session' => $this->session->session) ) );
    } catch( Exception $e ) {
      \Terminus::error("Login failed. %s", $e->getMessage());
    }

    $json = $resp->getBody(TRUE);

    return array(
      'info' => $resp->getInfo(),
      'headers' => $resp->getRawHeaders(),
      'json' => $json,
      'data' => json_decode($json)
    );
  }

  /**
   * Divert a request to our local cache of a fixtured data for testing
   *
   * Since the fixturing is based on the @global $argv we don't need args
   * @todo I'm not sure that I'm happy with the fixturing as is BUT it's
   * something to start with.
   */
  protected function fixtured_request() {
    if ( !$response = Fixtures::get("response") ) {
      Terminus::error("Oops, we don't seem to have a fixture for this request.
      Maybe you should try running scripts/build_fixtures.sh and then try again.");
    }
  }

  protected function _validateSiteUuid($site) {
    if (\Terminus\Utils\is_valid_uuid($site) && property_exists($this->sites, $site)){
      $this->_siteInfo =& $this->sites[$site];
      $this->_siteInfo->site_uuid = $site;
    } elseif($this->_siteInfo = $this->fetch_site($site)) {
      $site = $this->_siteInfo->site_uuid;
    } else {
      Terminus::error("Unable to locate the requested site.");
    }
    return $site;
  }

  protected function _constructTableForResponse($data) {
    $table = new \cli\Table();
    if (is_object($data)) {
      $data = (array)$data;
    }

    // if we've only a multidimensional array, give it an index to prevent
    // having conditional logic depending on the "shape" of the array
    if ( count($data) === count($data, COUNT_RECURSIVE) ) {
      $data = array(
        0=>$data,
      );
    }


    if( count($data) > 1 ) {
      if (property_exists($this, "_headers") && array_key_exists($this->_func, $this->_headers)) {
        $table->setHeaders($this->_headers[$this->_func]);
      } else {
        $table->setHeaders(array_keys($data[0]));
      }

      foreach ($data as $row => $row_data) {
        $row = array();
        foreach( $row_data as $key => $value) {
          if( is_array($value) OR is_object($value) ) {
            $value = join(", ",(array) $value);
          }
          $row[] = $value;
        }
        $table->addRow($row);
      }
    } else {
      $table->setHeaders(array("name", $data[0]['name']));
      unset($data[0]['name']);
      foreach( $data[0] as $key=>$value ) {

        if( is_array($value) OR is_object($value) ) {
          $value = implode(", ",(array) $value);
        }
        $table->addRow( array( $key, $value ) );
      }
    }

    $table->display();
  }

  protected function _handleFuncArg(array &$args = array() , array $assoc_args = array()) {

    // backups-delete should execute backups_delete function
    if (!empty($args)){
      $this->_func = str_replace("-", "_", array_shift($args));
      if (!is_callable(array($this, $this->_func), false, $static)) {
        if (array_key_exists("debug", $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        Terminus::error("I cannot find the requested task to perform it.");
  	  }
    }
  }

  protected function _handleSiteArg(&$args, $assoc_args = array()) {
    $uuid = null;
    if( !@$this->sites ) { $this->fetch_sites(); }
    if (array_key_exists("site", $assoc_args)) {
      $uuid = $this->_validateSiteUuid($assoc_args["site"]);
    } else  {
      Terminus::error("Please specify the site with --site=<sitename> option.");
    }
    if (!empty($uuid) && property_exists($this->sites, $uuid)) {
      $this->_siteInfo = $this->sites->$uuid;
      $this->_siteInfo->site_uuid = $uuid;
    } else {
      if (array_key_exists("debug", $assoc_args)){
        $this->_debug(get_defined_vars());
      }
      Terminus::error("Please specify the site with --site=<sitename> option.");
    }
  }

  protected function _handleEnvArg(&$args, $assoc_args = array()) {
    if (array_key_exists("env", $assoc_args)) {
      $this->_getEnvBindings($args, $assoc_args);
    } else  {
      Terminus::error("Please specify the site => environment with --env=<environment> option.");
    }

    if (!is_object($this->_bindings)) {
      if (array_key_exists("debug", $assoc_args)){
        $this->_debug(get_defined_vars());
      }
      Terminus::error("Unable to obtain the bindings for the requested environment.\n\n");
    } else {
      if (property_exists($this->_bindings, $assoc_args['env'])) {
        $this->_env = $assoc_args['env'];
      } else {
        Terminus::error("The requested environment either does not exist or you don't have access to it.");
      }
    }
  }

  protected function _getEnvBindings(&$args, $assoc_args) {
    $b = $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/'. $this->_env .'/bindings', "GET");
    if (!empty($b) && is_array($b) && array_key_exists("data", $b)) {
      $this->_bindings = $b['data'];
    }
  }

  protected function _execute( array $args = array() , array $assoc_args = array() ){
    $success = $this->{$this->_func}( $args, $assoc_args);
    if (array_key_exists("debug", $assoc_args)){
      $this->_debug(get_defined_vars());
    }
    if (!empty($success)){
      if (is_array($success) && array_key_exists("data", $success)) {
        if (array_key_exists("json", $assoc_args)) {
          echo \Terminus\Utils\json_dump($success["data"]);
        } else {
          $this->_constructTableForResponse($success['data']);
        }
      } elseif (is_string($success)) {
        echo Terminus::line($success);
      }
    } else {
      if (array_key_exists("debug", $assoc_args)){
        $this->_debug(get_defined_vars());
      }
      Terminus::error("There was an error attempting to execute the requested task.\n\n");
    }
  }

  protected function _debug($vars) {
    Terminus::line(print_r($this, true));
    Terminus::line(print_r($vars, true));
  }

}
