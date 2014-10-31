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
   * @subcommand branch-create
   * ## Options
   * --site=<site>
   * : site to create branch of
   * --branch=<branch>
   * : name of new branch
  **/
  public function branch_create($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $branch = preg_replace('#[-_\s]+#',"",@$assoc_args['branch']);
    $branch = $site->createBranch($branch);
  }

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
   * Show a list of your sites on Pantheon
   *
   * ## Options
   * --site=<site>
   * : Site to use
   * [--env=<env>]
   * : Environment
   * [--nocache]
   * : Bypass cache
   * [--latest]
   * : show the most recent backup
   */
   public function backups($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $env = $this->getValidEnv($site->getName(), $assoc_args['env']);
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
      $this->_constructTableForResponse($data, array('File','Size','Date'));
      return $data;
    }

   }

 /**
  * ## Options
  * @subcommand get-backup
  * --site=<site>
  * : Site to load
  * [--env=<env>]
  * : Environment to load
  * [--element=<code|files|db>]
  * : Element to download
  * [--to-directory=<directory>]
  * : Download the file if set
  */
   public function get_backup($args, $assoc_args) {
     $site = SiteFactory::instance($assoc_args['site']);
     $env = $this->getValidEnv($site->getName(), $assoc_args['env']);

     // prompt for backup type
     if (!$element = @$assoc_args['element']) {
       $element = Terminus::menu(array('code','files','database'), null, "Select type backup", TRUE);
     }

     if (!in_array($element,array('code','files','database'))) {
       Terminus::error("Invalid backup element specified.");
     }

     if (!$backups = $this->cache->get_data("{$site->getName()}:$env:{$element}")) {
       $backups = $site->environment($env)->backups($element);
       $this->cache->put_data("{$site->getName()}:$env:{$element}", $backups);
     }
     $menu = $folders = array();

     // build a menu for selecting back ups
     foreach( $backups as $backup ) {
       if (!isset($backup->filename)) continue;
       $buckets[] = $backup->folder;
       $menu[] = $backup->filename;
     }

     $index = Terminus::menu($menu, null, "Select backup");
     $bucket = $buckets[$index];
     $filename = $menu[$index];

     $url = $site->environment($env)->backupUrl($bucket,$element);

     if (isset($assoc_args['download-to'])) {
       Terminus::line("Downloading ... please wait ...");
       $filename = \Terminus\Utils\get_filename_from_url($url->url);
       $target = sprintf("%s/%s", $assoc_args['download-to'], $filename);
       if (Terminus_Command::download($url->url,$target)) {
         Terminus::success("Downloaded %s", $target);
         return $target;
       } else {
         Terminus::error("Could not download file");
       }
     }
     print $url->url;
     return;
   }

  /**
   * ## Options
   * @subcommand load-backup
   * --site=<site>
   * : Site to load
   * [--env=<env>]
   * : Environment to load
   * [--element=<code|files|db>]
   * : Element to download
   * [--to-directory=<directory>]
   * : Download the file if set
   */
   public function load_backup($args, $assoc_args) {
      $assoc_args['download-to'] = '/tmp';
      $assoc_args['element'] = 'database';
      $database = @$assoc_args['database'] ?: false;
      $username = @$assoc_args['username'] ?: false;
      $password = @$assoc_args['password'] ?: false;

      exec("mysql -e 'show databases'",$stdout, $exit);
      if ( 0 != $exit ) {
        Terminus::error("MySQL does not appear to be installed on your server.");
      }

      $target = $this->get_backup($args, $assoc_args);

      if (!file_exists($target)) {
        Terminus::error("Can't read database file %s", array($target));
      }

      Terminus::line("Unziping database");
      exec("gunzip $target", $stdout, $exit);

      // trim the gz of the target
      $target = Terminus\Utils\sql_from_zip($target);
      $target = escapeshellarg($target);

      if (!$database)
        $database = escapeshellarg(Terminus::prompt("Name of database to import to"));
      if (!$username)
        $username = escapeshellarg(Terminus::prompt("Username"));
      if (!$password)
        $password = escapeshellarg(Terminus::prompt("Password"));

      exec("mysql $database -u $username -p'$password' < $target", $stdout, $exit);
      if (0 != $exit) {
        Terminus::error("Could not import database");
      }

      Terminus::success("%s successfuly imported to %s", array($target, $database));
      return true;

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
   * @subcommand clone-env
   * ## Options
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
    * --site=<site>
    * : Site to deploy from
    * [--env=<env>]
    * : Environment to deploy to
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
    * @deprecated
    */
    private function getAvailableEnvs($site) {
      return $this->getEnvironments($site);
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
    $toReturn = $this->getEnvironments($assoc_args['site']);
    $this->_constructTableForResponse($toReturn);
    return $toReturn;
  }

  // @TODO this is going away and will be replaced by Site and Environment Objects
  private function getEnvironments($site) {
    $site = SiteFactory::instance($site);
    $environments = $site->availableEnvironments();
    return $environments;
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
   * Deploy dev environment to test or live
   *
   * ## Options
   * --site=<site>
   * : Site to deploy from
   * [--env=<env>]
   * : Environment to deploy to
   * [--cc]
   * : Clear cache after deploy?
   * [--update]
   * : (Drupal only) run update.php after deploy?
  **/
  public function jobs($args, $assoc_args) {
    $site = SiteFactory($assoc_args['site']);
    print_r($site->jobs());
  }


  /**
   * Show upstream updates
   * @subcommand upstream-info
   * ## Options
   * --site=<site>
   * : Site to check
   */
  public function upstream_info($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $upstream = $site->getUpstream();
    $this->_constructTableForResponse((array) $upstream);

  }

  /**
   * Show upstream updates
   * @subcommand upstream-updates
   * ## Options
   * --site=<site>
   * : Site to check
   * [--apply-to=<env>]
   * : A flag to apply to a specified environment
   */
   public function upstream_updates($args, $assoc_args) {
     $site = SiteFactory::instance($assoc_args['site']);
     $upstream = $site->getUpstreamUpdates();

     // data munging as usual
     $data = array();
     $data['dev'] = ( @$upstream->dev->is_up_to_date_with_upstream ) ?"Up-to-date":"Updates Available";
     if (isset($upstream->test)) {
       $data['test'] = ( @$upstream->test->is_up_to_date_with_upstream ) ?"Up-to-date":"Updates Available";
     }
     if (isset($upstream->live)) {
       $data['test'] = ( @$upstream->test->is_up_to_date_with_upstream ) ?"Up-to-date":"Updates Available";
     }

     $this->_constructTableForResponse($data, array('Environment','Status') );
     if (!empty((array) $upstream->update_log)) {
       $data = array();
       foreach ((array) $upstream->update_log as $commit) {
         $data = array(
           'hash' => $commit->hash,
           'datetime'=> $commit->datetime,
           'message' => $commit->message,
           'author' => $commit->author,
         );
         $this->_constructTableForResponse($data);
         echo PHP_EOL;
       }
     }

     if (isset($assoc_args['apply-to'])) {
       $env = $this->getValidEnv($site->getName(),$assoc_args['apply-to']);
       Terminus::confirm(sprintf("Are you sure you want to apply the upstream updates to %s:%s", $site->getName(), $env));
       $response = $site->applyUpstreamUpdates($env);
       $this->waitOnWorkflow('sites', $site->getId(), $response->id);
     }

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
