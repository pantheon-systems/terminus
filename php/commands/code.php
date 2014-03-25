<?php

use \Terminus\Dispatcher,
  \Terminus\Utils,
  \Terminus\CommandWithSSH;


class Code_Command extends CommandWithSSH {


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
   * : Additional argument flag(s) to pass in to the command.
   */
  function __invoke(array $args, array $assoc_args ) {
    if (empty($args) ) {
      Terminus::error("You need to specify a task to perform, site and envrionment on which to perform.");
    } else {
		  $this->_handleFuncArg($args, $assoc_args);
		  $this->_handleSiteArg($args, $assoc_args);
    }
    $this->_execute($args, $assoc_args);
  }


  /**
   * API Call to Get Code Commit Information
   */
  function log(array $args, array $assoc_args ) {
    //TODO: format output
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'code-log', 'GET');
  }

  /**
   * API Call to Get Core Status
   */
  function upstream_info(array $args, array $assoc_args ) {
    //TODO: format output
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'code-upstream-updates', "GET");
  }

  /**
   * API Call to get branches.
   */
  function tips(array $args, array $assoc_args ) {
    //TODO: format output
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, "code-tips", "GET");
  }

  /**
   * API Call to create branch.
   */
  function branchcreate(array $args, array $assoc_args ) {
    $branch_name = array_shift($args);
    $data = array(
      'refspec' => 'refs/heads/' . $branch_name,
    );
    //TODO: format output

    return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'code-branch', "POST", $data);
  }

  /**
   * API Call to delete branch.
   */
  function branchdelete(array $args, array $assoc_args) {
    $branch_name = array_shift($args);
    $data = array(
      'refspec' => 'refs/heads/' . $branch_name,
    );
    //TODO: format output
    return $this->terminus_request("site", $this->_siteInfo->site_uuid, "code-branch", "DELETE", $data);
  }

  /**
   * API Call to Update Core Status
   * [--updatedb=<true|false>]
   * : specify the site on which the command should be performed
   * [--xoption=<theirs|mine>]
   * : specifies the merge option
   */
  function upstreamupdate(array $args, array $assoc_args) {

    $path = 'code-upstream-updates';
    $data = array(
      // update database with latest schema
      'updatedb' => (array_key_exists("updatedb", $assoc_args)?$assoc_args['updatedb']:false),
      // default is to allow updates to parent repo to overwrite local
      'xoption' => (array_key_exists("xoption")) ? $assoc_args['updatedb'] : 'theirs',
    );
    //TODO: format output

    return $this->terminus_request("site", $this->_siteInfo->site_uuid, $path, "POST", $data);
  }

}

Terminus::add_command( 'code', 'Code_Command' );

