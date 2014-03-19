<?php
/**
 * actions on an individual site
 *
 */

use Terminus\Utils;

class Site_Command extends Terminus_Command {
  
  private $siteInfo;
    
  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush commands you intend to run.
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   */
  function __invoke( $args, $assoc_args ) {
    $func = array_shift($args);
    $success = null;
    if (empty($args)) {
      Terminus::error("You need to specify a site on which to request information.");
    } else {
      $site = array_shift($args);
      $uuid = $this->_validateSiteUuid($site);
    }
    if (method_exists($this, $func)) {
      $success = $this->$func( $args, $assoc_args);
    } else {
      Terminus::error("No forumla for requested artwork");
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
      Terminus::error("No forumla for requested artwork");
    }
  }
  
  public function info( $args, $assoc_args) {
    return $this->terminus_request("site", $this->siteInfo->site_uuid, "", "GET");
  }
  
  public function state($args, $assoc_args) {
    return $this->terminus_request("site", $this->siteInfo->site_uuid, "state", "GET");
  }  
  
  
  private function _validateSiteUuid($site) {
    if (\Terminus\Utils\is_valid_uuid($site) && property_exists($this->sites, $site)){
      $this->siteInfo =& $this->sites[$site];
      $this->siteInfo->site_uuid = $site;
    } elseif($this->siteInfo = $this->fetch_site($site)) {
      $site = $this->siteInfo->site_uuid;
    } else {
      Terminus::error("Unable to locate the requested site.");
    }
    return $site;
  }
  
  private function _constructTableForResponse($data) {
    $table = new \cli\Table();
    $table->setHeaders(array("Key", "Value"));
    if (is_object($data)) {
      $data = (array)$data;
    }
    foreach ($data as $key => $value) {
      if (is_array($value) || is_object($value)) {
        $value = implode(",", (array)$value);
      }
      $table->addRow(array($key, $value));
    }
    $table->display();
  }
  
}

Terminus::add_command( 'site', 'Site_Command' );
