<?php
/**
 * actions on an individual site
 *
 */

use Terminus\Utils;

class Site_Command extends Terminus_Command {
  
    
  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush commands you intend to run.
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   */
  function __invoke(array $args, array $assoc_args ) {
    if (empty($args)) {
      Terminus::error("You need to specify a task to perform and a site on which to perform it.");
    } else {
	    $this->_handleFuncArg($args, $assoc_args);
	    $this->_handleSiteArg($args, $assoc_args);
    }
    $this->_execute($args, $assoc_args);
  }
  
  public function info($args, $assoc_args) {
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, "", "GET");
  }
  
  /**
   * get site state
   *
   * @param string $args 
   * @param string $assoc_args 
   * @return array
   * @author stovak
   */
  
  public function state($args, $assoc_args) {
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, "state", "GET");
  }  
  
  /**
   * List enviroments for a site
   */
  function environments($args, $assoc_args) {
    return terminus_request("site", $this->_siteInfo->site_uuid, "environments", "GET");
  }
  
}

Terminus::add_command( 'site', 'Site_Command' );
