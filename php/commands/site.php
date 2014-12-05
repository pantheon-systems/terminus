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
use \Terminus\Loggers\Regular as Logger;

class Site_Command extends Terminus_Command {

  public function __construct() {
    parent::__construct();
  }

  protected $_headers = false;

  /**
  *
  * ## OPTIONS
  *
  * --site=<site>
  * : site to check attributes on
  *
  * [--env=<env>]
  * : environment
  *
  * ## EXAMPLES
  *
  **/
  public function attributes($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $data = $site->attributes();
    $this->handleDisplay($data, array(), array('Attribute','Value'));
  }

  /**
   *
   * ## OPTIONS
   *
   * --site=<site> : site to create branch of
   * --branch=<branch> : name of new branch
   *
   * ## EXAMPLES
   *
   * @subcommand branch-create
  **/
  public function branch_create($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $branch = preg_replace('#[-_\s]+#',"",@$assoc_args['branch']);
    $branch = $site->createBranch($branch);
  }

  /**
   * ## OPTIONS
   *
   * --site=<site>
   * : site to use
   *
   * [--env=<env>]
   * : Environment to clear
   *
   * ## EXAMPLES
   *  terminus site clear-caches --site=test
   *
   * @subcommand clear-caches
   */
  public function clear_caches($args, $assoc_args) {
      $site = SiteFactory::instance($assoc_args['site']);
      $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
      $response = $site->environment($env)->workflow("clear_cache");
      $this->waitOnWorkFlow('sites', $site->getId(), $response->id);
      Terminus::success("Caches cleared");
  }

  /**
   * Code related commands
   *
   * ## OPTIONS
   *
   * <action>
   * : options are log,branches,diffstat,commit
   *
   * --site=<site>
   * : name of the site
   *
   * [--env=<env>]
   * : site environment
   *
   * [--message=<message>]
   * : message to use when committing on server changes
   *
   */
  public function code($args, $assoc_args) {
      $subcommand = array_shift($args);
      $site = SiteFactory::instance(@$assoc_args['site']);
      $data = $headers = array();
      switch($subcommand) {
        case 'log':
          $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
          $logs = $site->environment($env)->log();
          $data = array();
          foreach ($logs as $log) {
            $data[] = array(
              'time' => $log->datetime,
              'author' => $log->author,
              'labels' => join(", ", $log->labels),
              'hash'  => $log->hash,
              'message' => trim(str_replace("\n",'',str_replace("\t",'',substr($log->message,0,50)))),
            );
          }
          break;
        case 'branches':
          $data = $site->tips();
          $headers = array('Branch','Commit');
          break;
        case 'commit':
          $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
          $diff = $site->environment($env)->diffstat();
          $count = count($diff);
          if (!Terminus::get_config('yes')) {
            Terminus::confirm("Commit %s changes?", $assoc_args, array($count));
          }
          $message = @$assoc_args['message'] ?: "Terminus commit.";
          $data = $site->environment($env)->onServerDev(null, $message);
          Terminus::success("Successfully commited.");
          \Terminus::launch_self('site',array('code','log'), array(
              'nocache' => true,
              'site' => $site->getName(),
              'env' => $env,
            )
          );
          return true;
          break;
        case 'diffstat':
          $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
          $diff = (array) $site->environment($env)->diffstat();
          if (empty($diff)) {
            Terminus::success("No changes on server.");
            return true;
          }
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
          break;
      }

      if(!empty($data)) {
        $this->handleDisplay($data, array(), $headers);
      }
      return $data;
  }

  /**
  * Connection related commands
  *
  * ## OPTIONS
  *
  * --site=<site>
  * : name of the site
  *
  * [--env=<env>]
  * : site environment
  *
  * [--set=<value>]
  * : set connection to sftp or git
  *
  * @subcommand connection-mode
  */
  public function connection_mode($args, $assoc_args) {
    $site = SiteFactory::instance(@$assoc_args['site']);
    $action = 'show';
    $mode = @$assoc_args['set'] ?: false;
    if (@$assoc_args['set']) {
      $action = 'set';
    }
    $env = $this->getValidEnv(@$site->getName(), @$assoc_args['env']);
    $data = $headers = array();
    switch($action) {
      case 'show':
        $data = $site->environment($env)->onServerDev();
        $mode = $data->enabled ? 'Sftp' : 'Git';
        Logger::GOutput("<Y>Connection mode:</Y> $mode");
        return;
        break;
      case 'set':
        if (!$mode) {
          Terminus::error("You must specify the mode with --set=<sftp|git>");
        }
        $data = $site->environment($env)->onServerDev($mode);
        break;
    }
    if(!empty($data)) {
      $this->handleDisplay($data, array(), $headers);
    }
    return $data;
  }

  /**
   * Open the Pantheon site dashboard a browser
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : site dashboard to open
   *
  */
  public function dashboard($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    Terminus::confirm("Do you want to open your dashboard link in a web browser?");
    $command = sprintf("open 'https://dashboard.getpantheon.com/sites/%s'", $site->getId());
    exec($command);
  }

  /**
   * ## OPTIONS
   *
   * --site=<site>
   * : name of the site to work with
   *
   * [--nocache]
   * : bypass the local cache
   * [--bash]
   * : bash friendly output
   *
   * ## EXAMPLES
   *
   */
  public function info($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $data = (array) $site->info();
    $this->handleDisplay($data,$args);
    return $data;

  }

 /**
  * Get, load, or create backup
  *
  *
  * ## OPTIONS
  *
  * <action> : function to run
  *
  * --site=<site> : Site to load
  *
  * [--env=<env>] : Environment to load
  * [--element=<code|files|db>] : Element to download
  * [--to-directory=<directory>] : Download the file if set
  *
  * ## EXAMPLES
  *
  */
   public function backup($args, $assoc_args) {
     $action = array_shift($args);
     if (!@$assoc_args['site']) Terminus::error("Must specify --site=<site>");
     $site = SiteFactory::instance($assoc_args['site']);
     $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
     switch ($action) {
       case 'get':
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

         if (empty($menu)) {
           Terminus::error("No backups available. Create one with `terminus site backup-make --site=%s`", array($site->getName()));
         }

         $index = Terminus::menu($menu, null, "Select backup");
         $bucket = $buckets[$index];
         $filename = $menu[$index];

         $url = $site->environment($env)->backupUrl($bucket,$element);

         if (isset($assoc_args['to-directory'])) {
           Terminus::line("Downloading ... please wait ...");
           $filename = \Terminus\Utils\get_filename_from_url($url->url);
           $target = sprintf("%s/%s", $assoc_args['to-directory'], $filename);
           if (Terminus_Command::download($url->url,$target)) {
             Terminus::success("Downloaded %s", $target);
             return $target;
           } else {
             Terminus::error("Could not download file");
           }
         }
         echo $url->url;
         return $url->url;
         break;
      case 'load':
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
        break;
      case 'create':
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
        $OPTIONS = array(
           'body' => json_encode($data) ,
           'headers'=> array('Content-type'=>'application/json')
         );
        $response = \Terminus_Command::request( "sites", $site_id, $path, 'POST', $OPTIONS);

        if( @$response['data']->id ) {
         $workflow_id = $response['data']->id;
         $result = $this->waitOnWorkFlow( 'sites', $response['data']->site_id, $workflow_id);
         if( $result ) {
           \Terminus::success("Successfully created backup");
         }
        }
        return true;
        break;
      case 'list':
      case 'default':
        $backups = $site->environment($env)->backups();
        $element = @$assoc_args['element'];
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
          $this->handleDisplay($data, $args, array('File','Size','Date'));
          return $data;
        }
      break;
    }
   }

  /**
   * ## OPTIONS
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
   * @subcommand clone-env
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


   // @todo this should be moved to a namespaced class CloneObject
   private function cloneObject($to_env, $from_env, $site_id, $object_type) {
     $path = sprintf("environments/%s/database", $to_env);
     $data = array('clone-from-environment'=>$from_env);
     $OPTIONS = array(
       'body' => json_encode($data) ,
       'headers'=> array('Content-type'=>'application/json')
     );
     $response = \Terminus_Command::request("sites", $site_id, $path, "POST", $OPTIONS);
     if ($response) {
       $this->waitOnWorkflow("sites", $site_id, $response['data']->id);
       return $response;
     }
     return false;
   }

  /**
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to use
   *
   * --env=<env>
   * : Pantheon environment create
   *
   * @subcommand create-env
   */
   public function create_env($args, $assoc_args) {
     Terminus::error("Feature currently unavailable. Please create environments in you pantheon dashboard at http://dashboard.getpantheon.com.");
     $env = $this->getValidEnv($assoc_args['site'], @$assoc_args['env']);
     $site_id = $this->getSiteId($assoc_args['site']);
     if ($this->envExists($site_id,$env)) {
       \Terminus::error("The %s environment already exists", array($env));
     }
     $path = sprintf('environments/%s', $env);
     $OPTIONS = array(
       'body' => json_encode(array()) ,
       'headers'=> array('Content-type'=>'application/json')
     );
     $response = \Terminus_Command::request('sites', $site_id, $path, 'POST', $OPTIONS);
    \Terminus::success("Created %s environment", array($env));

   }

   /**
    * Deploy dev environment to test or live
    *
    * ## OPTIONS
    *
    * --site=<site>
    * : Site to deploy from
    *
    * [--env=<env>]
    * : Environment to deploy to
    *
    * [--cc]
    * : Clear cache after deploy?
    *
    * [--update]
    * : (Drupal only) run update.php after deploy?
    *
    */
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
   private function getValidEnv($site, $env = null, $message = false) {
     $envs = SiteFactory::instance($site)->availableEnvironments();

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
   * List enviroments for a site
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Name of site to check
   *
   */
  function environments($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $environments = $site->environments();
    $data = array();
    foreach ($environments as $env) {
      $data[] = array(
        'Name' => $env->name,
        'Created' => $env->environment_created,
        'Domain' => $env->domain(),
        'OnServer Dev?' => $env->on_server_development ? 'true' : 'false',
        'Locked?' => $env->lock->locked ? 'true' : 'false',
      );
    }
    $this->handleDisplay($data,$args);
    return $data;
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
   * Hostname operations
   *
   * ## OPTIONS
   *
   * <list|add|remove>
   * : OPTIONS are list, add, delete
   *
   * --site=<site>
   * : Site to use
   *
   * --env=<env>
   * : environment to use
   *
   * [--hostname=<hostname>]
   * : hostname to add
   *
   */
   public function hostnames($args, $assoc_args) {
     $action = array_shift($args);
     $site = SiteFactory::instance(@$assoc_args['site']);
     $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
     switch ($action) {
       case 'list':
        $hostnames = $data = (array) $site->environment($env)->hostnames();
        if (!Terminus::get_config('json')) {
          // if were not just dumping the json then we should reformat the data
          $data = array();
          foreach ($hostnames as $hostname => $details ) {
            $data[] = array_merge( array('domain' => $hostname), (array) $details);
          }
        }
        $this->handleDisplay($data);
        break;
       case 'add':
          if (!isset($assoc_args['hostname'])) {
            Terminus::error("Must specify hostname with --hostname");
          }
          $data = $site->environment($env)->hostnameadd($assoc_args['hostname']);
          if (Terminus::get_config('verbose')) {
            \Terminus\Utils\json_dump($data);
          }
          Terminus::success("Added %s to %s-%s", array( $assoc_args['hostname'], $site->getName(), $env));
          break;
       case 'remove':
          if (!isset($assoc_args['hostname'])) {
            Terminus::error("Must specify hostname with --hostname");
          }
          $data = $site->environment($env)->hostnamedelete($assoc_args['hostname']);
          Terminus::success("Deleted %s from %s-%s", array( $assoc_args['hostname'], $site->getName(), $env));
        break;
     }
     return $data;
   }

  /**
   * ## OPTIONS
   *
   * <info|add|remove>
   * : action to execute ( i.e. info, add, remove )
   *
   * --site=<site>
   * : site name
   *
   * [--env=<env>]
   * : site environment
   *
   * [--username=<username>]
   * : your username
   *
   * [--password=<password>]
   * : your password
   *
  **/
  function lock($args, $assoc_args) {
    $action = array_shift($args);
    $site = SiteFactory::instance(@$assoc_args['site']);
    $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
    switch ($action) {
      case 'info':
        $data = $locks = $site->environment($env)->lockinfo();
        if (!Terminus::get_config('json')) {
          $data = array($data);
        }
        $this->handleDisplay($data);
        break;
      case 'add':
        Terminus::line("Creating new lock on %s -> %s", array($site->getName(), $env));
        if (!isset($assoc_args['username'])) {
          $email = Terminus::prompt("Username for the lock");
        } else {
          $email = $assoc_args['username'];
        }
        if (!isset($assoc_args['password'] ) ) {
          exec("stty -echo");
          $password = Terminus::prompt( "Password for the lock" );
          exec("stty echo");
          Terminus::line();
        } else {
          $password = $assoc_args['password'];
        }
        $data = $site->environment($env)->lock($email, $password);
        if ( property_exists($data,'id') ) {
          $this->waitOnWorkflow('sites',$data->site_id, $data->id);
        }
        Terminus::success('Success');
        break;
      case 'remove':
        Terminus::line("Removing lock from %s -> %s", array($site->getName(), $env));
        $data = $site->environment($env)->unlock();
        if ( property_exists($data,'id') ) {
          $this->waitOnWorkflow('sites',$data->site_id, $data->id);
        }
        Terminus::success('Success');
    }
  }
  /**
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to use
   *
   * --url=<url>
   * : Archive to import
   *
   * @subcommand import
   */
  public function import($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    if (!isset($assoc_args['url'])) {
      Terminus::error("You must specify a url for the archive you want to import.");
    }

    $import = $site->import($url);
    if ($import) {
      Terminus::success("Import queued");
    }
  }

  /**
   * Deploy dev environment to test or live
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to deploy from
  **/
  public function jobs($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $jobs = $site->jobs();
    $data = array();
    foreach ($jobs as $job) {
      $data[] = array(
        'slot' => $job->slot,
        'name' => $job->human_name,
        'env'  => @$job->environment,
        'status'  => $job->status,
        'updated' => $job->changed
      );
    }
    $this->handleDisplay($data,$args);
  }

  /**
   * Mount a site with sshfs
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to deploy from
   *
   * --destination=<path>
   * : local directory to mount
   *
   * [--env=<env>]
   * : Environment (dev,test)
   *
  **/
  public function mount($args, $assoc_args) {
    exec("which sshfs", $stdout, $exit);
    if ($exit !== 0) {
      Terminus::error("Must install sshfs first");
    }

    $destination = \Terminus\Utils\destination_is_valid($assoc_args['destination']);

    $site = SiteFactory::instance($assoc_args['site']);
    $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);

    // Darwin check ... not sure what this is really ... borrowed from terminus 1
    $darwin = false;
    exec('uname', $output, $ret);
    if (is_array($output) && isset($output[0]) && strpos($output[0], 'Darwin') !== False) {
     $darwin = True;
    }

    // @todo I'd prefer this was done with sprintf for a little validation
    $user = $env.'.'.$site->getId();
    $host = 'appserver.' . $env . '.' . $site->getId() . '.drush.in';
    $darwin_args = $darwin ? '-o defer_permissions ' : '';
    $cmd = "sshfs " . $darwin_args . "-p 2222 {$user}@{$host}:./ {$destination}";
    exec($cmd, $stdout, $exit);
    if ($exit !== 0) {
      print_r($stdout);
      Terminus::error("Couldn't mount $destination");
    }
    Terminus::success("Site mounted to %s. To unmount, run: umount %s ( or fusermount -u %s ).", array($destination,$destination,$destination));
  }

  /**
  * Get New Relic Info for site
  *
  * ## OPTIONS
  *
  * --site=<site>
  * : site for which to retreive notifications
  *
  * @subcommand new-relic
  */
  public function new_relic($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $data = $site->newRelic();
    $this->handleDisplay($data->account,$assoc_args,array('Key','Value'));
  }

  /**
  * Open the Pantheon site dashboard a browser
  *
  * ## OPTIONS
  *
  * --site=<site>
  * : site for which to retreive notifications
  *
  */
  public function notifications($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $notifications = $site->notifications();
    $data = array();
    foreach ($notifications as $note) {
      $data[] = array(
        'time'  => $note->start,
        'name'  => $note->name,
        'id'    => $note->build->number."@".$note->build->environment->HOSTNAME,
        'status'=> $note->build->status,
        'phase' => $note->build->phase,
        'duration' => $note->build->estimated_duration,
      );
    }
    $this->handleDisplay($data);
  }

  /**
  * Get or set owner
  *
  * ## OPTIONS
  *
  * --site=<site>
  * : Site to check
  *
  * [--set=<value>]
  * : new owner to set
  *
  * @subcommand owner
  */
  public function owner($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $data = $site->owner();
    $this->handleOutput($data);
  }

  /**
   * Interacts with redis
   *
   * ## OPTIONS
   *
   *    <subcommands> :
            clear - Clear redis cache on remote server
   *
   *    --site=<site> : site name
   *
   *    [--env=<env>] : environment
   *
   * ## Examples
   *
   *    terminus site redis clear --site=mikes-wp-test --env=live
   *
   */
  public function redis($args, $assoc_args) {
    $action = array_shift($args);
    $site = SiteFactory::instance(@$assoc_args['site']);
    $env = @$assoc_args['env'];
    switch ($action) {
      case 'clear':
        $bindings = $site->bindings('cacheserver');
        $commands = array();
        foreach($bindings as $binding) {
          if ( @$env AND $env != $binding->environment) continue;
          // @$todo ... should probably do this with symfony Process lib
          $args = array( $binding->environment, $site->getId(), $binding->environment, $site->getId(), $binding->host, $binding->port, $binding->password );
          array_filter($args, function($a) { return escapeshellarg($a); });
          $commands[$binding->environment] = vsprintf(
            'ssh -p 2222 %s.%s@appserver.%s.%s.drush.in "redis-cli -h %s -p %s -a %s flushall"',
            $args
          );
        }
        foreach ($commands as $env => $command) {
          Terminus::line("Clearing redis on %s ", array($env));
          exec($command, $stdout, $return);
          echo Logger::greenLine($stdout[0]);
        }
        break;
    }
  }

  /**
   * Get or set service level
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to check
   *
   * [--set=<value>]
   * : new service level to set
   *
   * @subcommand service-level
   */
  public function service_level($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $info = $site->info('service_level');
    if (@$assoc_args['set']) {
      $data = $site->updateServiceLevel($assoc_args['set']);
      Logger::coloredOutput(sprintf("%2<K>Service Level has been updated to '%s'%n", $info));
    }
    Logger::coloredOutput("%2<K>Service Level is '$info'%n");
    return true;
  }

  /**
  * Get or set team members
  *
  * ## OPTIONS
  *
  * <action> : i.e. add or remove
  *
  * --site=<site> : Site to check
  *
  * [--member=<email>] : Email of the member to add. Member will receive an invite
  *
  * @subcommand team
  */
  public function team($args, $assoc_args) {
    $action = array_shift($args) ?: 'list';
    $site = SiteFactory::instance($assoc_args['site']);
    $data = array();
    switch($action) {
      case 'add-member':
        $team = $site->teamAddMember($assoc_args['member']);
        Logger::coloredOutput("%2<K>Team member added!</K>");
        break;
      case 'remove-member':
        $team = $site->teamRemoveMember($assoc_args['member']);
        Logger::coloredOutput("%2<K>Team member removed!</K>");
        break;
      case 'list':
      case 'default':
        $team = $site->team();
        foreach ($team as $uuid => $user) {
          $data[] = array(
            'First' => $user->profile->firstname,
            'Last'  => $user->profile->lastname,
            'Email' => $user->email,
            'UUID'  => $uuid,
          );
        }
        ksort($data);
        break;
    }
    if (!empty($data)) {
      $this->handleDisplay($data);
    }
  }

  /**
   * Show upstream updates
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to check
   *
   * @subcommand upstream-info
   */
  public function upstream_info($args, $assoc_args) {
    $site = SiteFactory::instance($assoc_args['site']);
    $upstream = $site->getUpstream();
    $this->handleDisplay($upstream,$args);
  }

  /**
   * Show upstream updates
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to check
   *
   * [--apply-to=<env>]
   * : A flag to apply to a specified environment
   *
   * @subcommand upstream-updates
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
     if (empty($upstream->update_log)) Terminus::success("No updates to show");
     $upstreams = (array) $upstream->update_log;
     if (!empty($upstreams)) {
       $data = array();
       foreach ($upstreams as $commit) {
         $data = array(
           'hash' => $commit->hash,
           'datetime'=> $commit->datetime,
           'message' => $commit->message,
           'author' => $commit->author,
         );
         $this->handleDisplay($data,$args);
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
   * Pings a site to ensure it responds
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : site to ping
   *
   * [--env=<env>]
   * : environment to ping
   *
   * ## Examples
   *  terminus site wake --site='testsite' --env=dev
  */
  public function wake($args, $assoc_args) {
    $site = SiteFactory::instance(@$assoc_args['site']);
    $env = $this->getValidEnv($site->getName(), @$assoc_args['env']);
    $data = $site->environment($env)->wake();
    if (!$data['success']) {
      Logger::redLine(sprintf("Could not reach %s", $data['target']));
      return;
    }

    if (!$data['styx']) {
      Logger::redLine("Pantheon headers missing, which isn't quite right.");
      return;
    }

    Logger::greenLine(sprintf( "OK >> %s responded in %s", $data['target'], $data['time']));

  }

  /**
   * Complete wipe and reset a site
   *
   * ## OPTIONS
   *
   * --site=<site>
   * : Site to use
   *
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
