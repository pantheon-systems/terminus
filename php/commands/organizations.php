<?php

/**
 * Print the pantheon art
 *
 */


class Organizations_Command extends Terminus_Command {
  
  /**
   * Commands specific to an environment
   *
   * <commands>...
   * [--site=<value>]
   * : specify the site on which the command should be performed
   * [--env=<value>]
   * : Specificy the environment of a site previously set with --site=
   *
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   */
  function __invoke(array $args, array $assoc_args ) {
    if (empty($args) || (!array_key_exists("site", $assoc_args)) || (!array_key_exists("org", $assoc_args))) {
      Terminus::error("You need to specify a task to perform, site and envrionment on which to perform.");
    } else {
		  $this->_handleFuncArg($args, $assoc_args);
    }
    $this->_execute($args, $assoc_args);
  }
  /**
   * API call to get a user's organizations.
   */
  function list($args, $assoc_args) {
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations', "GET");
  }

  /**
   * API call to get sites within an organization.
   *
   * Available only to organization admins.
   */
  function sites($args, $assoc_args) {
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/'. $this->_org .'/sites', "GET");
  }

  /**
   * API call to add a site into an organization.
   * [--site=<value>]
   * : specify the site on which the command should be performed (may be name or UUID)
   *
   * Available only to organization admins.
   */
  function site_add($args, $assoc_args) {
    if (array_key_exists("site", $assoc_args)) {
      $site_uuid = $this->_validateSiteUuid($assoc_args["site"]);
    }
    if (empty($site_uuid)) {
      Terminus::error("You must specify the site to remove with --site=");
      return false;
    }
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/' . $this->_org . '/sites/' . $site_uuid, "PUT");
  }

  /**
   * API call to remove a site from an organization.
   * [--site=<value>]
   * : specify the site on which the command should be performed (may be name or uuid)
   *
   * Available only to organization admins.
   */
  function site_remove($args, $assoc_args) {
    if (array_key_exists("site", $assoc_args)) {
      $site_uuid = $this->_validateSiteUuid($assoc_args["site"]);
    }
    if (empty($site_uuid)) {
      Terminus::error("You must specify the site to remove with --site=");
      return false;
    }
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/'. $this->_org .'/sites/'. $site_uuid, "DELETE");
  }

  /**
   * API call to get users within an organization.
   *
   * Available only to organization admins.
   */
  function users($args, $assoc_args) {
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/'. $this->_org .'/users', "GET");
  }

  /**
   * API call to add a user to an organization.
   *
   * Available only to organization admins.
   *
   * @todo: promote/demote
   */
  function useradd($args, $assoc_args) {
    $user_to_add = array_shift($args);
    if ($admin) {
      $path .= '?admin=1';
    }
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/'. $this->_org .'/users/'. $user_to_add, "PUT");
  }

  /**
   * API call to remove a user from an organization.
   *
   * Available only to organization admins.
   */
  function userremove($args, $assoc_args) {
    $user_to_delete = array_shift($args);
    //TODO: format output
    return $this->terminus_request("user", $this->_uuid, 'organizations/'. $organization_uuid .'/users/'. $user_to_delete, "DELETE");
  }
  
  
  
}


Terminus::add_command( 'organizations', 'Organizations_Command' );
