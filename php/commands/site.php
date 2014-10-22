<?php
/**
 * actions on an individual site
 *
 */

use Terminus\Utils;
use \Guzzle\Http\Client;

class Site_Command extends Terminus_Command {

  protected $_headers = array(
    "environments" => array("Environment","Created", "Locked")
  );

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush commands you intend to run.
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   */
  function __invoke(array $args, array $assoc_args ) {
    if( !isset($this->session->user_uuid) ) {
      \Terminus::error("You must first login using `terminus auth login`");
    }
    if (empty($args)) {
      \Terminus::error("You need to specify a task to perform and a site on which to perform it.");
    } else {
      $this->_handleFuncArg($args, $assoc_args);
      $this->_handleSiteArg($args, $assoc_args);
    }
    $this->_execute($args, $assoc_args);
  }

  /**
   * ## Options
   * <site>
   * : name of the site to work with
   * [--<nocache>]
   * : bypass the local cache
   */
  public function info($args, $assoc_args) {
    return (array) $this->terminus_request("site", $this->_siteInfo->site_uuid, "", "GET");
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
    return $this->terminus_request("sites", $this->_siteInfo->site_uuid, "", "GET");
  }

  /**
   * list backups
   * Show a list of your sites on Pantheon
   *
   * @package Terminus
   * @version 1.5
   *
   * ## OPTIONS
   * <env>
   * : site environment to list backups from
   *
   * [--nocache]
   * [--site]
   * : (required) site name
   * [--latest]
   * : show the most recent backup
   */
   public function backups($args, $assoc_args) {
    $env = $this->getValidEnv(@$assoc_args['env']);

    // if $latest is set we'll filter the output list
    $latest = @$assoc_args['latest'] ?: false;
    $folder = '';
    if ($latest) {
      $folder = $this->getLatestBucket($assoc_args['site']);
    }
    // try cache first
    $toReturn = $this->cache->get_data("backup-catalog-{$assoc_args['site']}-$env$folder");

    // hit api if necessary
    if ( isset($assoc_args['nocache']) OR !$toReturn ) {
      $site_id = $this->getSiteId( $assoc_args['site'] );
      if( !$site_id ) \Terminus::error("Could not find site %s", array($assoc_args['site']) );
      $path = sprintf("environments/%s/backups/catalog", $env);
      $backups = $this->terminus_request('site', $site_id, $path );
      if( count( (array) $backups['data']) < 1 ) {
        \Terminus::success("No backup found. Create one using `terminus site backup-make`");
      }

      // format the response data for better display
      $toReturn = array();
      $toReturn['backups'] = $backups['data'];
      foreach( $backups['data'] as $backup ) {
        if (!@$backup->filename ) continue;
        if (!empty($folder) AND $backup->folder != $folder) continue;
        $toReturn['data'][] = array(
          'filename' => $backup->filename,
          'finished'=> date('F j,Y H:i:s', $backup->finish_time ),
          'folder' => $backup->folder,
        );
      }
      $this->cache->put_data("backup-catalog-{$assoc_args['site']}-$env$folder", $toReturn );
    }
    return $toReturn;
   }

    /*
    * ## OPTIONS
    * <site>
    * : site to get backups from
    *
    * [--env]
    * : Include code in backup? default 'yes'

    * [--folder]
    * : Backup folder to retrieve
    */
   public function backups_urls($args, $assoc_args) {
     $assoc_args['folder'] = @$assoc_args['folder'] ?: '';
     $assoc_args['env'] = @$assoc_args['env'] ?: 'dev';

     // if a folder isn't specified then just grab the latest folder
     if (empty($assoc_args['folder'])) {
       $assoc_args['folder'] = $this->getLatestBucket($assoc_args['site']);
     }

     $elements = array('code','database','files');
     $toReturn = array();
     $urls = array();
     foreach ($elements as $element) {
       $urls[] = $this->getBackupUrl( $assoc_args['site'],$assoc_args['env'], $assoc_args['folder'], $element);
     }
     $toReturn['data'] = $urls;

     return $toReturn;
   }

   /*
   * ## OPTIONS
   * <env>
   * : site environment to run backup from
   *
   * [--code]
   * : Include code in backup? default 'yes'
   * [--file]
   * : Include media and files in backup? default 'no'
   * [--db]
   * : Include dump of database? default 'yes'
   */
   public function backup_make($args, $assoc_args) {
     $env = $this->getValidEnv( @$assoc_args['env'] );

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
     $options = array(
        'body' => json_encode($data) ,
        'headers'=> array('Content-type'=>'application/json')
      );
     $response = $this->terminus_request( "sites", $site_id, $path, 'POST', $options );
     if( @$response['data']->id ) {
      $workflow_id = $response['data']->id;
      $result = $this->waitOnWorkFlow( 'sites', $response['data']->site_id, $workflow_id );
      if( $result ) {
        \Terminus::success("Successfully created backup");
      }
     }

     return true;
   }

  /**
   * ## OPTIONS
   * [--from-env]
   * : Environment you want to clone from
   * [--to-env]
   * : Environment you want to clone to
   * [--db]
   * : Clone the database? (bool) default no
   * [--files]
   * : Clone the files? (bool) default no
   */
   public function clone_env($args, $assoc_args) {
     $site_id = $this->getSiteId($assoc_args['site']);
     $from_env = $this->getValidEnv(@$assoc_args['from-env'], "Choose environment you want to clone from");
     $to_env = $this->getValidEnv(@$assoc_args['to-env'], "Choose environment you want to clone to");

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
        $assoc_args['env'] = $to_env;
        $this->create_env($args, $assoc_args);
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

   private function cloneObject($to_env, $from_env, $site_id, $object_type) {
     $path = sprintf("environments/%s/database", $to_env);
     $data = array('clone-from-environment'=>$from_env);
     $options = array(
       'body' => json_encode($data) ,
       'headers'=> array('Content-type'=>'application/json')
     );
     $response = $this->terminus_request("sites", $site_id, $path, "POST", $options);
     if ($response) {
       $this->waitOnWorkflow("sites", $site_id, $response['data']->id);
       return $response;
     }
     return false;
   }

  /**
   * ## OPTIONS
   * [--env]
   * : Pantheon environment create
   */
   public function create_env($args, $assoc_args) {
     $env = $this->getValidEnv(@$assoc_args['env']);
     $site_id = $this->getSiteId($assoc_args['site']);
     if ($this->envExists($site_id,$env)) {
       \Terminus::error("The %s environment already exists", array($env));
     }
     $path = sprintf('environments/%s', $env);
     $options = array(
       'body' => json_encode(array()) ,
       'headers'=> array('Content-type'=>'application/json')
     );
     $response = $this->terminus_request('sites', $site_id, $path, 'POST', $options);
    \Terminus::success("Created %s environment", array($env));

   }

   /**
    * Deploy dev environment to test or live
    *
    * ## OPTIONS
    * <env>
    * : Environment to deploy to
    * <site>
    * : Site to deploy from

    * [--cc]
    * : Clear cache after deploy?
    * [--update]
    * : (Drupal only) run update.php after deploy?
   **/
   public function deploy($args, $assoc_args) {
     $env = $this->getValidEnv(@$assoc_args['env']);

     $cc = $update = 0;
     if (array_key_exists('cc',$assoc_args)) {
       $cc = 1;
     }
     if (array_key_exists('update',$assoc_args)) {
       $update = 1;
     }

     $params = array(
       'update' => $update,
       'cc' => $cc
     );
     $site_id = $this->getSiteId($assoc_args['site']);
     $path = sprintf('environments/%s/code?%s', $env, http_build_query($params));
     $response = $this->terminus_request('sites', $site_id, $path, 'POST');
     $result = $this->waitOnWorkflow('sites', $sites_id, $response['data']->id);
     if ($result) {
       \Terminus::success("Woot! Code deployed to %s", array($env));
     }
   }

  /**
   * Fetch a valid environment
   */
   private function getValidEnv( $env = null, $message = false ) {
     $envs = $this->getAvailableEnvs();
     if (!$message) {
       $message = "Specify a environment";
     }

     if (!$env OR array_search($env, $envs) === false) {
       $env = \Terminus::menu( $envs , null, $message );
       $env = $envs[$env];
     }

     if (!$env) {
       \Terminus::error("Environment '%s' unavailable", array($env));
     }

     return $env;
   }

  /**
    * Fetch available environments
    */
    private function getAvailableEnvs() {
      $is_available = "Not Locked";
      $envs = $this->environments(array(), array());
      $available = array();
      foreach( $envs['data'] as $env ) {
        if( $env[2] == $is_available ) {
          $available[] = $env[0];
        }
      }
      return $available;
    }

  /**
   * Fetch the UUID for a site name
   */
   private function getSiteId( $name ) {
      $lookup = array();
      foreach( $this->sites as $uuid => $site ) {
        $lookup[$site->information->name] = $uuid;
      }

      if (array_key_exists($name, $lookup)) {
        return $lookup[$name];
      }
      return false;
   }

  /**
   * List enviroments for a site
   */
  function environments($args, $assoc_args) {
    $results = $this->terminus_request("sites", $this->_siteInfo->site_uuid, "environments", "GET");
    $toReturn = array();
    foreach ($results['data'] as $key => $value) {
      $toReturn['data'][] = array(
        $key,
        date('jS F Y h:i:s A (T)', $value->environment_created),
        ( $value->lock->locked ? "Locked" : "Not Locked" )
      );
    }
    return $toReturn;
  }

  /**
   * List enviroments for a site
   */
   function envExists($site_id, $env) {
     $response = $this->terminus_request('sites', $site_id, 'environments/live/code-log', 'GET');
     $envs = (array) $response['data'];
     return array_key_exists($env, $envs);
   }

   private function getBackupUrl($site,$env,$bucket,$element) {
     //this is confusing, but is for some reason required by the api
     //@TODO fix this
     $data = array(
       'method' => 'GET'
     );
     $method = 'POST';
     $options = array(
       'body' => json_encode($data) ,
       'headers'=> array('Content-type'=>'application/json')
     );

     $path = sprintf('environments/%s/backups/catalog/%s/%s/s3token', $env, $bucket, $element );
     $response = $this->terminus_request('sites', $this->getSiteId($site), $path, 'POST', $options );
     return $response['data']->url;
   }

   /**
    * Get the most recent backup's bucket
    */
   private function getLatestBucket($site) {
    // casting is ugly
    $backups = (array) $this->backups( array(), array('env'=>'dev', 'site' => $site) );
    $backups = (array) $backups['backups'];
    $last = end($backups);
    if (!is_object($last)) {
      \Terminus::error('No backups found.');
    }
    return $last->folder;
   }

   /**
    * ## OPTIONS
    * [--from-env]
    * : Environment you want to clone from
    * [--to-env]
    * : Environment you want to clone to
    * [--db]
    * : Clone the database? (bool) default no
    * [--files]
    * : Clone the files? (bool) default no
    */
    function diff() {

    }
}

\Terminus::add_command( 'site', 'Site_Command' );
