<?php

/**
 * Commands specific to an environment
 *
 */


class Environment_Command extends \Pantheon\Command {
  
    protected $_headers = array(
      "backups" => array("ID","Type", "Date", "Bucket", "Size")
    );
    
    protected $_realm = "site";
    protected $_realmUUID;
    
    function __construct($args, $assoc_args) {
      parent::__construct($args, $assoc_args);
      $this->init($args, $assoc_args);
    }
  
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
    function init(array $args, array $assoc_args ) {     
      if ((!array_key_exists("site", $assoc_args)) || (!array_key_exists("env", $assoc_args))) {
        throw new \Pantheon\Exception("You need to specify a site and envrionment on which to perform.");
      } else {
        //process the site id argment
  		  $this->_handleSiteArg($args, $assoc_args);
        $this->_realmUUID = $this->_siteInfo->getUUID();
        // if we are not creating or deleting an org, go ahead and process the org argment
  		  $this->_handleEnvArg($args, $assoc_args);
      }
    }
    
	  /**
	   * list backups for a specific site => env
     * 
     *
     * <commands>...
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     * [--<flag>=<value>]
     * : Additional Drush flag(s) to pass in to the command.
     *
	   * @param array $args 
	   * @param array $assoc_args 
	   * @return void
	   * @author stovak
	   */
    
    public function backups($args, $assoc_args) {    
      return $this->request("environments/".$assoc_args['env']."/backups/catalog", "GET", array(), "BackupList")->respond($assoc_args);
    }
    
    /**
     *  Retrieve a backup URL from the catalog for this site => environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
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
        return $this->request($path, "POST", array( 'method' => 'GET' ), "BackupList")->respond($assoc_args);
      }
    }
    
    /**
     * Backup Now
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     * [--all]
     * : Backup files, db and code
     * 
     * [--files]
     * : Backup files for a given site
     * 
     * [--db]
     * : Backup db for a given site
     *
     * [--code]
     * : Backup code for a given site
     *
     * @author stovak
     */
    
    public function backupnow($args, $assoc_args) {
      
      $code = false;
      $db = false;
      $files = false;
      
      switch(true) {
        
        case array_key_exists("all", $assoc_args): 
          $code = true;
          $db = true;
          $files = true;
          break;
          
        case array_key_exists("code", $assoc_args):
          $code = true;
          break;
        
        case array_key_exists("db", $assoc_args):
          $db = true;
          break;
          
        case array_key_exists("files", $assoc_args):
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
      $this->request('environments/' . $assoc_args['env'] . '/backups/create', "POST", $data);
      \Terminus::line("backup started on ".$assoc_args['site']."::".$assoc_args['env']);
      return true;
    }
    
    /**
     * Create an environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    function create($args, $assoc_args) {
      if (!array_key_exists("env", $assoc_args)) {
        throw new \Pantheon\Exception("You must provide a site name with the switch --site=<sitename>");
      } else {
        $response = $this->request( 'environments/' . $env, "POST");
        \Terminus::line("New environment creation ".$assoc_args['site']."::".$assoc_args['env']);
        return true;
      }    
    }

    /**
     * Delete an environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    function delete($args, $assoc_args) {
      if (!array_key_exists("env", $assoc_args)) {
        throw new \Pantheon\Exception("You must provide a site name with the switch --site=<sitename>");
      } else {
        $response = $this->request( 'environments/' . $assoc_args['env'], "POST");
        \Terminus::line("New environment creation ".$assoc_args['site']."::".$assoc_args['env']);
        return true;
      } 
    }
    
    /**
     * lock an environment.
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
*
     * [--username]
     * : Your patheon username
     * 
     * [--download]
     * : Your pantheon password
     *
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
        $response = $this->request( 'environments/' . $assoc_args['env'] . '/lock', "PUT", $data);
        return true;
      }
    }

    /**
     * Delete an environment lock.
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    public function unlock($args, $assoc_args) {
      return $this->request( 'environments/' . $assoc_args['env'] . '/lock', "DELETE");
    }

    /**
     * Get Info on an environment lock
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    public function lockinfo($args, $assoc_args) {
      return $this->request('environments/'.$assoc_args['env'].'/lock', "GET");
    }
    
    /**
     * list hotnames for environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    public function hostnames($args, $assoc_args) {
      return $this->request( 'environments/' . $assoc_args['env'] . '/hostnames', "GET", array(), "HostnameList")->respond($assoc_args);
    }

    /**
     * Add hostname to environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    public function hostnameadd($args, $assoc_args) {
      $hostname = array_shift($args);
      return $this->request('environments/' . $assoc_args['env'] . '/hostnames/' . rawurlencode($hostname), "PUT");
    }

    /**
     * Delete hostname from environment
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     */
    public function hostnamedelete($args, $assoc_args) {
      $hostname = array_shift($args);
      return $this->request('environments/' . $assoc_args['env'] . '/hostnames/' . rawurlencode($hostname), "DELETE");
    }
       
    /**
     * undocumented function
     *
     * [--site=<value>]
     * : specify the site on which the command should be performed (may be name or UUID)
     *
     * [--env=<value>]
     * : Specificy the environment of a site previously set with --site=
     *
     *
     * @param string $bid 
     * @return void
     * @author stovak
     */
    
    private function _backupIdIsValidBackup($bid) {
      $backup_list = $this->request("environments/{$this->_env}/backups/catalog", "GET");
      if (!property_exists($backup_list['data'], $bid)) {
        Terminus::error( "Requested backup does not exist for site and environment." );
      } else {
        return $backup_list['data']->{$bid};
      }
    }
  
}

Terminus::add_command( 'environment', 'Environment_Command' );

