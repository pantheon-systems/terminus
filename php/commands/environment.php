<?php

/**
 * Commands specific to an environment
 *
 */


class Environment_Command extends Terminus_Command {

    protected $_headers = array(
      "backups" => array("ID","Type", "Date", "Bucket", "Size")
    );

    /**
     * Commands specific to an environment
     *
     * <commands>...
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     * [--<flag>=<value>]
     * : Additional Drush flag(s) to pass in to the command.
     */
    function __invoke(array $args, array $assoc_args ) {
      if (empty($args) || (!array_key_exists("site", $assoc_args)) || (!array_key_exists("org", $assoc_args))) {
        Terminus::error("You need to specify a task to perform, site and environment on which to perform.");
      } else {
        // process the function argument
  		  $this->_handleFuncArg($args, $assoc_args);
        //process the site id argment
  		  $this->_handleSiteArg($args, $assoc_args);
        // if we are not creating or deleting an org, go ahead and process the org argment
        if (!in_array($this->_func, array("create", "delete"))) {
    		  $this->_handleEnvArg($args, $assoc_args);
        }
      }
      $this->_execute($args, $assoc_args);
    }
	  /**
	   * list backups for a specific site => env
	   *
	   * @param array $args
	   * @param array $assoc_args
	   * @return void
	   * @author stovak
	   */
    public function backups($args, $assoc_args) {
      $results = $this->terminus_request("site", $this->_siteInfo->site_uuid, "environments/{$this->_env}/backups/catalog", "GET");
      $table = array();
      foreach ($results['data'] as $key => $value) {
         $table[] = array(
           $key,
           @array_pop(explode("_", $key)),
           date('jS F Y h:i:s A (T)', $value->timestamp),
           $value->folder,
           number_format((($value->size/1024)/1024), 1)."MB"
         );
      }
      return array("data" => $table);
    }

    /**
     *  Retrieve a backup URL from the catalog for this site => environment
     *
     * [--json]
     * : return the value in json.
     *
     * [--download]
     * : download the backup rather than return the url
     *
     * @param string $args
     * @param string $assoc_args
     * @return void
     * @author stovak
     */
    public function backup($args, $assoc_args) {
      // todo: account for --json and --download
      // todo: default behavior should be to return url
      $BID = array_shift($args);

      if ($this->_backupIdIsValidBackup($BID)) {
        $aBID = explode("_", $BID);
        $path = 'environments/' . $this->_env . '/backups/catalog/' . $aBID[0] . "_" . $aBID[1] . '/' . $aBID[2] . '/s3token';
        $burl = $this->terminus_request("site", $this->_siteInfo->site_uuid, $path, "POST", array(
          'method' => 'GET',
          )
        );
        if (count($assoc_args)) {
          $output = array_shift($assoc_args);
          switch ($output) {

            case "json":
              return $burl['data'];
              break;

            case "download":
              Terminus::line("Downloading backup...");
              passthru("curl -OL \"{$burl['data']->url}\"");
              return "Downloaded";
              break;

            case "default":
              return $burl['data']->url;
            }
        }
      }
    }

    /**
     * Backup Now
     *
     * @author stovak
     */

    public function backupnow($args, $assoc_args) {
      $type = array_shift($args);

      $code = false;
      $db = false;
      $files = false;

      switch($type) {

        case "all":
          $code = true;
          $db = true;
          $files = true;
          break;

        default:
          $$type = true;
      }

      $data = array(
        'entry_type' => $entry_type,
        'scheduled_for' => time(),
        'code' => ($code) ? 1 : 0,
        'database' => ($db) ? 1 : 0,
        'files' => ($files) ? 1 : 0,
      );
      return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/' . $this->_env . '/backups/create', "POST", $data);
    }

    /**
     * Create an environment
     */
    function create($args, $assoc_args) {
      $env = array_shift($args);
      return $this->terminus_request("site", $this->_siteInfo->site_uuid , 'environments/' . $env, "POST");
    }

    /**
     * Delete an environment
     */
    function delete($args, $assoc_args) {
      $env = array_shift($args);
      return $this->terminus_request("site", $this->_siteInfo->site_uuid , 'environments/' . $env, "DELETE");
    }

    /**
     * lock an environment.
     *
     * [--username]
     * : Your patheon username
     *
     * [--download]
     * : Your pantheon password
     */
    public function lock($args, $assoc_args) {
      if (array_key_exists("username", $assoc_args)) {
        $username= $assoc_args['username'];
      }
      if (array_key_exists("password", $assoc_args)) {
        $password = $assoc_args['password'];
      }
      if (empty($username) || empty($password)) {
        Terminus::error("You must specify --username= and --password= to lock an environment.");
      } else {
        $data = array('username' => $username, 'password' => $password);
        return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/' . $this->_env . '/lock', "PUT", $data);
      }
    }

    /**
     * Delete an environment lock.
     */
    public function unlock($args, $assoc_args) {
      return $this->terminus_request("site", $this->_siteInfo->site_uuid,  'environments/' . $this->_env . '/lock', "DELETE");
    }

    /**
     * Get Info on an environment lock
     */
    public function lockinfo($args, $assoc_args) {
      return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/'.$this->_env.'/lock', "GET");
    }

    /**
     * list hotnames for environment
     */
    public function hostnames($args, $assoc_args) {
      return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/' . $environment . '/hostnames', $method);
    }

    /**
     * Add hostname to environment
     */
    public function hostnameadd($args, $assoc_args) {
      $hostname = array_shift($args);
      return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/' . $this->_env . '/hostnames/' . rawurlencode($hostname), "PUT");
    }

    /**
     * Delete hostname from environment
     */
    public function hostnamedelete($args, $assoc_args) {
      $hostname = array_shift($args);
      return $this->terminus_request("site", $this->_siteInfo->site_uuid, 'environments/' . $this->_env . '/hostnames/' . rawurlencode($hostname), "DELETE");
    }








    /**
     * undocumented function
     *
     * @param string $bid
     * @return void
     * @author stovak
     */

    private function _backupIdIsValidBackup($bid) {
      $backup_list = $this->terminus_request("site", $this->_siteInfo->site_uuid, "environments/{$this->_env}/backups/catalog", "GET");
      if (!property_exists($backup_list['data'], $bid)) {
        Terminus::error( "Requested backup does not exist for site and environment." );
      } else {
        return $backup_list['data']->{$bid};
      }
    }

}

Terminus::add_command( 'environment', 'Environment_Command' );
