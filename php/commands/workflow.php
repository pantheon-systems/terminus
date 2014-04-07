<?php
/**
 * Actions on multiple sites
 *
 */
class Workflow_Command extends Terminus_Command {
  
  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush commands you intend to run.
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   */
  
  function __invoke($args, $assoc_args) {
    $this->_handleFuncArg($args, $assoc_args);
    $this->_handleSiteArg($arts, $assoc_args);
    $this->_handleEnvArg($arts, $assoc_args);
    return $this->_execute($args, $assoc_args);
  }
  
  /**
   * API call to clone a database between site environments.
   */
  public function clone_database($site_uuid, $env_source, $env_target, $updatedb = 0) {
    $env_target = array_shift($args);
    $path = 'environments/' . $env_target . '/database';
    $data = array(
      'clone-from-environment' => $this->_env,
      'updatedb' => $updatedb,
    );
    //TODO: format output
    return $this->terminus_request("site", $this->_siteInfo->uuid, $path, "POST", $data);
  }

  /**
   * API call to clone user files between site environments.
   */
  public function clone_files($args, $assoc_args) {
    $env_target = array_shift($args);
    $path = 'environments/' . $env_target . '/files';
    $data = array(
      'clone-from-environment' => $this->_env,
    );
    return $this->terminus_request("site", $this->_siteInfo->uuid, $path, "POST", $data);
  }

  /**
   * API call to deploy code to a site environment.
   */
  public function deploy_code($args, $assoc_args) {
    $env_target = array_shift($args);
    $path = 'environments/' . $env_target . '/code';
    $arguments = array();
    if (array_key_exists("updatedb", $assoc_args)) {
      $arguments[] = 'updatedb=1';
    }
    if (array_key_exists("clearcache", $assoc_args)) {
      $arguments[] = 'clearcache=1';
    }
    if (!empty($arguments)) {
      $path .= '?' . implode('&', $arguments);
    }
    $data = array();
    //TODO: format the returned data
    return $this->terminus_request("site", $this->_siteInfo->uuid, $path, "POST", $data);
  }

  /**
   * API call to wipe a site environment.
   */
  public function content_wipe($args, $assoc_args) {
    return terminus_request("site", $this->_siteInfo->uuid, 'environments/' . $this->_env . '/wipe', "POST", "");
  }
  
  
}

Terminus::add_command( 'workflow', 'Workflow_Command' );
