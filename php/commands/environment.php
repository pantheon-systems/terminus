<?php

/**
 * Commands specific to an environment
 *
 */
use Terminus\SiteFactory;
require "site.php";

class Environment_Command extends Site_Command {

    /**
     * Show a list of your sites on Pantheon
     *
     * ## Options
     * env=<env>
     *
     * --site=<site>
     * : Site to use
     *
     * [--env=<env>]
     * : Environment
     * [--nocache]
     * : Bypass cache
     * [--latest]
     * : show the most recent backup
     *
     * ## EXAMPLES
     *
     * @alias list
     */
     public function backups($args, $assoc_args) {
      $site = SiteFactory::instance($assoc_args['site']);
      $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
      $backups = $site->environment($env)->backups();

      $data = array();
      foreach ($backups as $id => $backup) {
        if (!isset($backup->filename)) continue;
        $data[] = array(
          $backup->filename,
          sprintf("%dMB", $backup->size / 1024 / 1024),
          date("Y-m-d H:i:s", $backup->finish_time),
        );
      }

      if (empty($backups)) {
        \Terminus::error("No backups found.");
        return false;
      } else {
        //munging data
        $this->handleDisplay($data,$args);
        return $data;
      }

     }

    /**
     *  Retrieve a backup URL from the catalog for this site => environment
     *
     * --site=<site>
     * : Site to backup
     *
     * [--env=<env>]
     * : environment to back up
     *
     * [--code]
     * : Include code in backup? default 'yes'
     *
     * [--files]
     * : Include media and files in backup? default 'no'
     *
     * [--db]
     * : Include dump of database? default 'yes'
     *
     * [--json]
     * : return the value in json.
     *
     * [--download]
     * : download the backup rather than return the url
     *
    */
    public function backup($args, $assoc_args) {
      $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);
      $type='backup';
      $path = sprintf('environments/%s/backups/create', $env);
      $site_id = $this->getSiteId( $assoc_args['site'] );

      $data = array(
       'entry_type' => $type,
       'scheduled_for' => time(),
       'code' =>  @$assoc_args['code'] ? true : false,
       'database' => @$assoc_args['db'] ? true : false,
       'files' => @$assoc_args['files'] ? true : false,
      );
      $Options = array(
         'body' => json_encode($data) ,
         'headers'=> array('Content-type'=>'application/json')
       );
      $response = \Terminus_Command::request( "sites", $site_id, $path, 'POST', $Options);

      if( @$response['data']->id ) {
       $workflow_id = $response['data']->id;
       $result = $this->waitOnWorkFlow( 'sites', $response['data']->site_id, $workflow_id);
       if( $result ) {
         \Terminus::success("Successfully created backup");
       }
      }
      return true;
    }

    /**
     * ## Options
     *
     * --site=<site>
     * : Site to use
     *
     * [--from-env]
     * : Environment you want to clone from
     *
     * [--to-env]
     * : Environment you want to clone to
     *
     * [--db]
     * : Clone the database? (bool) default no
     *
     * [--files]
     * : Clone the files? (bool) default no
     *
     * @subcommand clone
     */
     public function clone_env($args, $assoc_args) {
       $site_id = $this->getSiteId($assoc_args['site']);
       $from_env = $this->getValidEnv($assoc_args['site'], @$assoc_args['from-env'], "Choose environment you want to clone from");
       $to_env = $this->getValidEnv($assoc_args['site'], @$assoc_args['to-env'], "Choose environment you want to clone to");

       $db = $files = false;
       $db = isset($assoc_args['db']) ?: false;
       $append = array();
       if ($db) {
         $append[] = "DATABASE";
       }
       $files = isset($assoc_args['files']) ?: false;
       if ($files) {
         $append[] = 'FILES';
       }
       $append = join(' and ', $append);

       if (!$files AND !$db) {
         \Terminus::error('You must specify something to clone using the the --db and --files flags');
       }

       $confirm = sprintf("Are you sure?\n\tClone from %s to %s\n\tInclude: %s\n", strtoupper($from_env), strtoupper($to_env), $append);
       \Terminus::confirm($confirm);

        if ( !$this->envExists($site_id, $to_env) ) {
          \Terminus::error("The %s environment has not been created yet. run `terminus site create-env --site=<env>`");
        }

       if ($db) {
         print "Cloning database ... ";
         $this->cloneObject( $to_env, $from_env, $site_id, 'database');
       }

       if ($files) {
        print "Cloning files ... ";
        $this->cloneObject( $to_env, $from_env, $site_id, 'database');
       }
       \Terminus::success("Clone complete!");
       return true;
     }

     /**
      * Clone helper
      */
     protected function cloneObject($to_env, $from_env, $site_id, $object_type) {
       $path = sprintf("environments/%s/database", $to_env);
       $data = array('clone-from-environment'=>$from_env);
       $Options = array(
         'body' => json_encode($data) ,
         'headers'=> array('Content-type'=>'application/json')
       );
       $response = \Terminus_Command::request("sites", $site_id, $path, "POST", $Options);
       if ($response) {
         $this->waitOnWorkflow("sites", $site_id, $response['data']->id);
         return $response;
       }
       return false;
     }

    /**
     * Create an environment
     *
     * <env>
     *
     * --site=<site>
     * : Site to use
     */
    function create($args, $assoc_args) {
      Terminus::error("Currently unavailable");
      $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);
      $site_id = SiteFactory::instance($assoc_args['site'])->getId();


      $path = sprintf('environments/%s', $env);
      $Options = array(
        'body' => json_encode(array()) ,
        'headers'=> array('Content-type'=>'application/json')
      );
      $response = \Terminus_Command::request('sites', $site_id, $path, 'POST', $Options);
      \Terminus::success("Created %s environment", array($env));
    }

    /**
     * Delete an environment
     */
    function delete($args, $assoc_args) {
      Terminus::error("Currently unavailable");
      $site = SiteFactory::instance($assoc_args['site']);
      $env = $this->getValidEnv($site->getName(),@$assoc_args['env']);
      if ($response = $site->deleteEnvironment($env)) {
        Terminus::success("Deleted environment");
      }
    }

    /**
     * lock an environment.
     *
     * --site=<site>
     * : Site on pantheon
     *
     * -- env=<env>
     * : Environment
     *
     * --username=<username>
     * : Your patheon username
     *
     * --password=<password>
     * : Your pantheon password
     *
     * @todo move to environment class
     */
    public function lock($args, $assoc_args) {
        $site = SiteFactory::instance(@$assoc_args['site']);
        $env = $this->getValidEnv($assoc_args['env']);
        $data = json_encode(array('username' => $username, 'password' => $password));
        $options = array(
          'body' => $data,
          'headers' => array('Content-type'=>'application/json')
        );
        return Terminus_Command::request("site", $site->getId(), 'environments/' . $this->_env . '/lock', "PUT", $options);
    }

    /**
     * Delete an environment lock.
     */
    public function unlock($args, $assoc_args) {
      return Terminus_Command::request("site", $site->getId(),  'environments/' . $this->_env . '/lock', "DELETE");
    }

    /**
     * Get Info on an environment lock
     */
    public function lockinfo($args, $assoc_args) {
      return Terminus_Command::request("site", $site->getId(), 'environments/'.$this->_env.'/lock', "GET");
    }

    /**
     * list hotnames for environment
     */
    public function hostnames($args, $assoc_args) {
      return Terminus_Command::request("site", $site->getId(), 'environments/' . $environment . '/hostnames', $method);
    }

    /**
     * Add hostname to environment
     */
    public function hostnameadd($args, $assoc_args) {
      $hostname = array_shift($args);
      return Terminus_Command::request("site", $site->getId(), 'environments/' . $this->_env . '/hostnames/' . rawurlencode($hostname), "PUT");
    }

    /**
     * Delete hostname from environment
     */
    public function hostnamedelete($args, $assoc_args) {
      $hostname = array_shift($args);
      return Terminus_Command::request("site", $site->getId(), 'environments/' . $this->_env . '/hostnames/' . rawurlencode($hostname), "DELETE");
    }








    /**
     * undocumented function
     *
     * @param string $bid
     * @return void
     * @author stovak
     */

    private function _backupIdIsValidBackup($bid) {
      $backup_list = Terminus_Command::request("site", $site->getId(), "environments/{$this->_env}/backups/catalog", "GET");
      if (!property_exists($backup_list['data'], $bid)) {
        Terminus::error( "Requested backup does not exist for site and environment." );
      } else {
        return $backup_list['data']->{$bid};
      }
    }

}

Terminus::add_command( 'environment', 'Environment_Command' );
