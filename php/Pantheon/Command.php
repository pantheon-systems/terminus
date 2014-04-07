<?php

/**
 * Base class for \Pantheon commands
 *
 * @package Terminus
 */
namespace Pantheon;

abstract class Command {

  public $cache;
  public $session;
  public $sites;

  protected $_func;
  protected $_siteInfo;
  protected $_environments;
  protected $_bindings;

  public function __construct($args, $assoc_args) {
    # Load commonly used data from cache.
    $this->cache = \Terminus::get_cache();
    $this->session = $this->cache->get_data('session');
    $this->sites = $this->cache->get_data('sites');
    // if this is an auth request do not check for a site list object
    if (!($this->sites instanceOf \Pantheon\Iterators\SiteList) && get_class($this) != "Auth_Command") {
      $this->_fetch_sites();
    }
  }

  /**
   * Actually go out and get the sites.
   */
  protected function _fetch_sites() {
    \Terminus::log('Fetching site list from Pantheon');
    $this->sites = \Pantheon\DataWrappers\Request::getResponse('user',
      $this->session->user_uuid,
      'sites',
      'GET',
      Array('hydrated' => TRUE),
      "SiteList");
    $this->cache->put_data('sites', $this->sites);
    return $this->sites;
  }

  /**
   * Helper function to grab a single site's data from cache if possible.
   */
  public function fetch_site($site_name, $nocache = FALSE) {
    if (empty($this->sites)) {
      $this->fetch_sites(TRUE);
    }
    $site = $this->sites->findByName($site_name);
    if ($site instanceOf \Pantheon\DataWrappers\Site) {
      return $site;
    }
    else {
      \Terminus::error("The site named '$site_name' does not exist or you don't have access to it. Run `\Terminus sites show` for a list of sites.");
    }
  }

  /**
   * Helper code to grab sites and manage local cache.
   */
  public function fetch_sites($nocache = FALSE) {
    return (($this->sites instanceOf \Pantheon\Iterators\SiteList) && ($nocache == FALSE)) ? $this->sites : $this->_fetch_sites();
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
   *
   * @return instanceOf \Pantheon\Iterators\Response
   */
  public function request($path = FALSE, $method = 'GET', $data = NULL, $class = "Response") {
    return \Pantheon\DataWrappers\Request::getResponse($this->_realm, $this->_realmUUID, $path, $method, $data, $class);
  }

  protected function _constructTableForResponse($data) {
    $table = new \cli\Table();
    if (is_object($data)) {
      $data = (array) $data;
    }
    if (property_exists($this, "_headers") && array_key_exists($this->_func, $this->_headers)) {
      $table->setHeaders($this->_headers[$this->_func]);
    }
    else {
      $table->setHeaders(array_keys($data));
    }
    foreach ($data as $row => $row_data) {
      $row = array();
      foreach ($row_data as $key => $value) {
        $row[] = $value;
      }
      $table->addRow($row);
    }
    $table->display();
  }

  protected function _handleFuncArg(array &$args = array(), array $assoc_args = array()) {
    // backups-delete should execute backups_delete function
    if (!empty($args)) {
      $this->_func = str_replace("-", "_", array_shift($args));
      if (!is_callable(array($this, $this->_func), FALSE, $static)) {
        if (array_key_exists("debug", $assoc_args)) {
          $this->_debug(get_defined_vars());
        }
        throw new \Pantheon\Exception("I cannot find the requested task to perform it.");
      }
    }
  }

  protected function _debug($vars) {
    \Terminus::line(print_r($this, TRUE));
    \Terminus::line(print_r($vars, TRUE));
  }

  protected function _handleSiteArg(&$args, $assoc_args = array()) {
    $uuid = NULL;
    if (array_key_exists("site", $assoc_args)) {
      $uuid = $this->_validateSiteUuid($assoc_args["site"]);
    }
    else {
      throw new \Pantheon\Exception("Please specify the site with --site=<sitename> option.");
    }
    if (!($this->_siteInfo instanceOf \Pantheon\DataWrappers\Site)) {
      throw new \Pantheon\Exception("I can't find the specified site ({$assoc_args['site']}).");
    }
  }

  protected function _validateSiteUuid($site) {
    if (\Terminus\Utils\is_valid_uuid($site)) {
      $this->_siteInfo = $this->sites->findByUUID($site);
    }
    else {
      $this->_siteInfo = $this->sites->findByName($site);
    }
    if (!$this->_siteInfo instanceOf \Pantheon\DataWrappers\Site) {
      \Pantheon\Exception("Unable to locate the requested site.");
    }
    else {
      return $this->_siteInfo->getUUID();
    }
  }

  protected function _handleEnvArg(&$args, $assoc_args = array()) {
    if (!array_key_exists("env", $assoc_args)) {
      throw new \Pantheon\Exception("Please specify the site => environment with --env=<environment> option.");
    }
    $this->_environments = $this->request('environments', "GET", array(), "EnvironmentList");
    if (!$this->_environments instanceOf \Pantheon\Iterators\EnvironmentList) {
      throw new \Pantheon\Exception("Unable to get a list of environments of the site.");
    }
    $this->_env = $this->_environments->findByName($assoc_args['env']);
    if (!$this->_env instanceOf \Pantheon\DataWrappers\Environment) {
      throw new \Pantheon\Exception("The requested environment either does not exist or you don't have access to it.");
    }
    $this->_bindings = $this->request('environments/' . $assoc_args['env'] . '/bindings', "GET", array(), "BindingsList");

    if (!$this->_bindings instanceOf \Pantheon\Iterators\BindingsList) {
      throw new \Pantheon\Exception("Unable to obtain the bindings for the requested environment.\n\n");
    }
  }

  protected function _execute(array $args = array(), array $assoc_args = array()) {
    return $this->{$this->_func}($args, $assoc_args);
  }

}

