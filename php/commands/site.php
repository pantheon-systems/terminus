<?php
/**
 * actions on an individual site
 *
 */

use Terminus\Utils;
use Terminus\Auth;
use Terminus\SiteFactory;
use Terminus\Site;
use \Guzzle\Http\Client;

class Site_Command extends Terminus_Command {

  public function __construct() {
    parent::__construct();
  }

  protected $_headers = array(
    "environments" => array("Environment","Created", "Locked")
  );

  /**
   * ## Options
   * --site=<site>
   * : name of the site to work with
   * --env=<env>
   * : environment to check
   * [--filter]
   * : Use a regex to filter the diffstat for certain files
   * [--nocache]
   * : bypass the local cache
   * [--bash]
   * : bash friendly output
   */
  public function diffstat($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);
    $diff = (array) $site->environment($env)->diffstat();
    $data = array();
    // munge the data
    $filter = @$assoc_args['filter'] ?: false;
    foreach ($diff as $file => $stats) {
      if ($filter) {
        $filter = preg_quote($filter,'/');
        $regex = '/'.$filter.'/';
        if (!preg_match($regex, $file)) {
          continue;
        }
      }
      $data[] = array_merge( array('file'=>$file), (array) $stats );
    }

    if ( @$assoc_args['bash'] )
      echo \Terminus\Utils\bash_out( (array) $data);
    else
      $this->_constructTableForResponse( (array) $data);

    return $diff;
  }

  /**
   * ## Options
   * --site=<site>
   * : name of the site to work with
   * [--nocache]
   * : bypass the local cache
   * [--bash]
   * : bash friendly output
   */
  public function info($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $toReturn = (array) $site->info();
    if ( @$assoc_args['bash'] )
      echo \Terminus\Utils\bash_out( (array) $toReturn);
    else
      $this->_constructTableForResponse( (array) $toReturn);
    return $toReturn;

  }

  /**
   * list backups
   * Show a list of your sites on Pantheon
   * ## Options
   * --site=<site>
   * : Site to use
   * [--nocache]
   * : Bypass cache
   * [--latest]
   * : show the most recent backup
   */
   public function backups($args, $assoc_args) {
    $toReturn = $this->getBackups($args, $assoc_args);
    //munging data
    if ( !$toReturn ) {
      \Terminus::error("No backups found.");
    }

    for( $i=0; $i<count($toReturn['data']); $i++ ) {
      $toReturn['data'][$i] = (array) $toReturn['data'][$i];
    }
    $this->_constructTableForResponse($toReturn['data']);
    return $toReturn;
   }

   /**
    * Retrieve all backups
    * This is a helper function that will eventually be moved into a Backup class
   **/
   private function getBackups($args, $assoc_args) {
     $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);

     // if $latest is set we'll filter the output list
     $latest = @$assoc_args['latest'] ?: false;
     $folder = '';

     // try cache first
     $toReturn = $this->cache->get_data("backup-catalog-{$assoc_args['site']}-$env$folder");

     // hit api if necessary
     if ( @$assoc_args['nocache'] OR !$toReturn ) {
       $site_id = $this->getSiteId( $assoc_args['site'] );
       if( !$site_id ) \Terminus::error("Could not find site %s", array($assoc_args['site']) );
       $path = sprintf("environments/%s/backups/catalog", $env);
       $backups = \Terminus_Command::request('site', $site_id, $path );
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
     return (array) $toReturn;
   }

  /**
   * @subcommand backups-urls
   * ## Options
   * --site=<site>
   * : Site to use
   * [--env]
   * : Include code in backup? default 'yes'
   * [--folder]
   * : Backup folder to retrieve
   * [--bash]
   * : Bash friendly output
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

     if (@$assoc_args['bash']) {
       echo \Terminus\Utils\bash_out($toReturn['data']);
     }
     return $toReturn;
   }


  /**
   * @subcommand backup-make
   * ## Options
   * --env=<env>
   * : site environment to run backup from
   * --site=<site>
   * : Site to use
   * [--code]
   * : Include code in backup? default 'yes'
   * [--file]
   * : Include media and files in backup? default 'no'
   * [--db]
   * : Include dump of database? default 'yes'
   */
   public function backup_make($args, $assoc_args) {
     $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env'] );
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
     $response = \Terminus_Command::request( "sites", $site_id, $path, 'POST', $Options );
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
   * ## Options
   * @subcommand clone-env
   * --site=<site>
   * : Site to use
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

   private function cloneObject($to_env, $from_env, $site_id, $object_type) {
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
   * @subcommand create-env
   * ## Options
   * --site=<site>
   * : Site to use
   * --env=<env>
   * : Pantheon environment create
   */
   public function create_env($args, $assoc_args) {
     Terminus::error("Feature currently unavailable. Please create environments in you pantheon dashboard at http://dashboard.getpantheon.com.");
     $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);
     $site_id = $this->getSiteId($assoc_args['site']);
     if ($this->envExists($site_id,$env)) {
       \Terminus::error("The %s environment already exists", array($env));
     }
     $path = sprintf('environments/%s', $env);
     $Options = array(
       'body' => json_encode(array()) ,
       'headers'=> array('Content-type'=>'application/json')
     );
     $response = \Terminus_Command::request('sites', $site_id, $path, 'POST', $Options);
    \Terminus::success("Created %s environment", array($env));

   }

   /**
    * Deploy dev environment to test or live
    *
    * ## Options
    * [--env=<env>]
    * : Environment to deploy to
    * --site=<site>
    * : Site to deploy from

    * [--cc]
    * : Clear cache after deploy?
    * [--update]
    * : (Drupal only) run update.php after deploy?
   **/
   public function deploy($args, $assoc_args) {
     $env = $this->getValidEnv(@$assoc_args['site'], @$assoc_args['env'], "Select environment to deploy to");

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
     $response = \Terminus_Command::request('sites', $site_id, $path, 'POST');
     $result = $this->waitOnWorkflow('sites', $site_id, $response['data']->id);
     if ($result) {
       \Terminus::success("Woot! Code deployed to %s", array($env));
     }
   }

  /**
   * Fetch a valid environment
   */
   private function getValidEnv($site, $env = null, $message = false ) {
     $envs = $this->getAvailableEnvs($site);

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
    private function getAvailableEnvs($site) {
      $is_available = "Not Locked";
      $envs = $this->getEnvironments(array(), array('site'=>$site));
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
      if( !$this->sites ) {
       $this->fetch_sites();
      }

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
   * @subcommand
   * ## Options
   * --site=<site>
   * : Name of site to check
   */
  function environments($args, $assoc_args) {
    $this->_handleSiteArg($args, $assoc_args);
    $toReturn = $this->getEnvironments($args, $assoc_args);
    $this->_constructTableForResponse($toReturn['data']);
    return $toReturn;
  }

  // @TODO this is going away and will be replaced by Site and Environment Objects
  private function getEnvironments($args, $assoc_args) {
    $results = \Terminus_Command::request("sites", $this->getSiteId($assoc_args['site']), "environments", "GET");
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
   private function envExists($site_id, $env) {
     $response = \Terminus_Command::request('sites', $site_id, 'code-tips', 'GET');
     $envs = (array) $response['data'];
     return array_key_exists($env, $envs);
   }

   /**
    * Get the Amazon url for a backup
    */
   private function getBackupUrl($site,$env,$bucket,$element) {
     //this is confusing, but is for some reason required by the api
     //@TODO fix this
     $data = array(
       'method' => 'GET'
     );
     $method = 'POST';
     $Options = array(
       'body' => json_encode($data) ,
       'headers'=> array('Content-type'=>'application/json')
     );

     $path = sprintf('environments/%s/backups/catalog/%s/%s/s3token', $env, $bucket, $element );
     $response = \Terminus_Command::request('sites', $this->getSiteId($site), $path, 'POST', $Options );
     return $response['data']->url;
   }

   /**
    * Get the most recent backup's bucket
    */
   private function getLatestBucket($site) {
    // casting is ugly
    $backups = (array) $this->getBackups( array(), array('env'=>'dev', 'site' => $site) );
    $backups = (array) $backups['backups'];
    $last = end($backups);
    if (!is_object($last)) {
      \Terminus::error('No backups found.');
    }
    return $last->folder;
   }

   /**
    * Complete wipe and reset a site
    @subcommand wipe
    * ## Options
    * --site=<site>
    * : Site to use
    * [--env=<env>]
    * : Specify environment, default = dev
    */
   public function wipe($args, $assoc_args) {
     try {
       $env = @$assoc_args['env'] ?: 'dev';
       $site = SiteFactory::instance($assoc_args['site']);
       $site_id = $site->getId();
       $env = $this->getValidEnv($assoc_args['site'], $env);
       Terminus::line("Wiping %s %s", array($site_id, $env));
       $resp = $site->environment($env)->wipe();
       if ($resp) {
         $this->waitOnWorkflow('sites', $site_id, $resp['data']->id);
         Terminus::success("Successfully wiped %s -- %s", array($site->getName(),$env));
       }
    } catch(Exception $e) {
      Terminus::error("%s",array($e->getMessage()));
    }
   }

}

\Terminus::add_command( 'site', 'Site_Command' );
