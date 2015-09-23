<?php

use Terminus\Utils;
use Terminus\Auth;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Models\Collections\Instruments;
use \Guzzle\Http\Client;
use \Terminus\Loggers\Regular as Logger;
use \Terminus\Helpers\Input;
use \Terminus\Deploy;
use Terminus\Models\Collections\Sites;

/**
 * Actions to be taken on an individual site
 */
class Site_Command extends TerminusCommand {
  protected $_headers = false;

  public function __construct() {
    parent::__construct();
    $this->sites = new Sites();
  }

  /**
  * Get or set site attributes
  *
  * ## OPTIONS
  *
  * [--site=<site>]
  * : site to check attributes on
  *
  * [--env=<env>]
  * : environment
  *
  * ## EXAMPLES
  *
  **/
  public function attributes($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $this->outputter->outputRecord($site->attributes);
  }

  /**
   * Clear all site caches
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : site to use
   *
   * [--env=<env>]
   * : Environment to clear
   *
   * ## EXAMPLES
   *  terminus site clear-cache --site=test
   *
   * @subcommand clear-cache
   */
  public function clear_cache($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env_id = Input::env($assoc_args, 'env');
    $workflow = $site->workflows->create(
      'clear_cache',
      array('environment' => $env_id)
    );
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Code related commands
   *
   * ## OPTIONS
   *
   * <log|branches|diffstat|commit>
   * : options are log, branches, diffstat, commit
   *
   * [--site=<site>]
   * : name of the site
   *
   * [--env=<env>]
   * : site environment
   *
   * [--message=<message>]
   * : message to use when committing on server changes
   */
  public function code($args, $assoc_args) {
    $subcommand = array_shift($args);
    $site = $this->sites->get(Input::sitename($assoc_args));
    $data = $headers = array();
    $env = $site->environments->get(Input::env($assoc_args, 'env'));
    switch ($subcommand) {
      case 'log':
        $logs = $env->log();
        $data = array();
        foreach ($logs as $log) {
          $data[] = array(
            'time'    => $log->datetime,
            'author'  => $log->author,
            'labels'  => implode(', ', $log->labels),
            'hash'    => $log->hash,
            'message' => trim(
              str_replace(
                "\n",
                '',
                str_replace("\t", '', substr($log->message, 0, 50))
              )
            ),
          );
        }
        break;
      case 'branches':
        $data = $site->tips();
        $headers = array('Branch', 'Commit');
        break;
      case 'commit':
        $diff    = $env->diffstat();
        $count   = count((array)$diff);
        $message = "Commit changes to $count files?";
        if ($count === 0) {
          $message = 'There are no changed files. Commit anyway?';
        }
        Terminus::confirm($message, $assoc_args);
        $message = @$assoc_args['message'] ?: 'Terminus commit.';
        $workflow = $env->commitChanges($message);
        $workflow->wait();
        $this->workflowOutput($workflow);
        return true;
        break;
      case 'diffstat':
        $diff = (array)$env->diffstat();
        if (empty($diff)) {
          Terminus::success('No changes on server.');
          return true;
        }
        $data = array();
        $filter = @$assoc_args['filter'] ?: false;
        foreach ($diff as $file => $stats) {
          if ($filter) {
            $filter = preg_quote($filter, '/');
            $regex = '/' . $filter . '/';
            if (!preg_match($regex, $file)) {
              continue;
            }
          }
          $data[] = array_merge(array('file' => $file), (array)$stats);
        }
        break;
      }

      if(!empty($data)) {
        $this->outputter->outputRecord($data, $headers);
      }
      return $data;
  }

  /**
  * Change connection mode between SFTP and Git
  *
  * ## OPTIONS
  *
  * [--site=<site>]
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
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $action = 'show';
    if (isset($assoc_args['set']) && $assoc_args['set']) {
      $action = 'set';
      $mode   = $assoc_args['set'];
    }

    # Only present dev and multidev environments; Test/Live cannot be modified
    $environments = array_diff($site->environments->ids(), array('test', 'live'));

    $env = Input::env($assoc_args, 'env', 'Choose environment', $environments);
    if (($env == 'test' || $env == 'live') && $action == 'set') {
      Terminus::error('Connection mode cannot be set in Test or Live environments');
    }
    $data = $headers = array();
    switch($action) {
      case 'set':
        if (!in_array($mode, array('sftp', 'git'))) {
          Terminus::error('You must specify the mode as either sftp or git.');
        }
        $workflow = $site->environments->get($env)->changeConnectionMode($mode);
        if (is_string($workflow)) {
          $this->logger->info($workflow);
        } else {
          $workflow->wait();
          $this->workflowOutput($workflow);
        }
        break;
      case 'show':
      default:
        $mode = $site->environments->get($env)->getConnectionMode();
        $this->outputter->outputRecord(array('connection_mode' => $mode));
        break;
    }
    return $data;
  }

  /**
   * Open the Pantheon site dashboard in a browser
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : site dashboard to open
   *
   * [--env=<env>]
   * : site environment to display in the dashboard
   *
   * [--print]
   * : don't try to open the link, just print it
   *
   * @subcommand dashboard
  */
  public function dashboard($args, $assoc_args) {
    switch (php_uname('s')) {
      case 'Linux':
        $cmd = 'xdg-open';
        break;
      case 'Darwin':
        $cmd = 'open';
        break;
      case 'Windows NT':
        $cmd = 'start';
        break;
    }
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = Input::optional('env', $assoc_args);
    if (isset($env) && ($env != null)) {
      $env = '#' . $env;
    } 
    $url = sprintf(
      'https://dashboard.pantheon.io/sites/%s%s',
      $site->get('id'),
      $env
    );
    if (isset($assoc_args['print'])) {
      $this->outputter->outputValue($url, 'Dashboard URL');
    }
    else {
      Terminus::confirm(
        'Do you want to open your dashboard link in a web browser?',
        Terminus::get_config()
      );
      $command = sprintf('%s %s', $cmd, $url);
      exec($command);
    }
  }

   /**
    * Delete a site from pantheon
    *
    * ## OPTIONS
    * [--site=<site>]
    * : UUID or name of the site you want to delete
    *
    * [--force]
    * : to skip the confirmations
    */
   public function delete($args, $assoc_args) {
     $site = $this->sites->get(Input::sitename($assoc_args));

     if (!isset($assoc_args['force']) && (!Terminus::get_config('yes'))) {
       //If the force option isn't used, we'll ask you some annoying questions
       Terminus::confirm(sprintf('Are you sure you want to delete %s?', $site->get('name')));
       Terminus::confirm('Are you really sure?');
     }
     Terminus::line(sprintf('Deleting %s ...', $site->get('name')));
     $response = $site->delete();
     $site->deleteFromCache();

     Terminus::success('Deleted %s!', $site->get('name'));
   }

  /**
   * Retrieve information about the site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of the site to work with
   *
   * [--field=<field>]
   * : field to return
   *
   * ## EXAMPLES
   *
   */
  public function info($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    # Fetch environment data for sftp/git connection info
    $site->environments->all();

    if (isset($assoc_args['field'])) {
      $field = $assoc_args['field'];
      $this->outputter->outputValue($site->info($field), $field);
    } else {
      $this->outputter->outputRecord($site->info());
    }
  }

  /**
   * Retrieve connection info for a specific environment
   * e.g. git, sftp, mysql, redis
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of the site
   *
   * [--env=<env>]
   * : environment for which to fetch connection info
   *
   * [--field=<field>]
   * : specific field to return
   *
   * @subcommand connection-info
   *
   */
  public function connection_info($args, $assoc_args) {
    $site        = $this->sites->get(Input::sitename($assoc_args));
    $env_id      = Input::env($assoc_args, 'env', 'Choose environment');
    $environment = $site->environments->get($env_id);
    $info        = $environment->connectionInfo();

    if (isset($assoc_args['field'])) {
      $field = $assoc_args['field'];
      Terminus::line($info[$field]);
    } else {
      $this->outputter->outputRecord($info);
    }
  }

  /**
   * List site organizations
   *
   * ## OPTIONS
   *
   * <list|add|remove>
   * : subfunction to run
   *
   * [--site=<site>]
   * : Site's name
   *
   * [--org=<name|id>]
   * : Organization to add/remove from membership, name or UUID
   *
   * [--role=<role>]
   * : Max role for organization on this site ... default "team_member"
   *
   */
  public function organizations($args, $assoc_args) {
    $action = array_shift($args);
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $data   = array();
    switch ($action) {
      case 'add':
        $role = Input::optional('role', $assoc_args, 'team_member');
        $org  = Input::orgname($assoc_args, 'org');
        if (!$this->isOrgAccessible($org)) {
          $this->logger->error(
            "Organization is either invalid or you are not a member."
          );
          exit(0);
        }
        $workflow = $site->org_memberships->addMember($org, $role);
        $workflow->wait();
        break;
      case 'remove':
        $org = Input::orgid($assoc_args, 'org');
        if (!$this->isOrgAccessible($org)) {
          $this->logger->error(
            "Organization is either invalid or you are not a member."
          );
          exit(0);
        }
        $member   = $site->org_memberships->get($org);
        if ($member == null) {
          Terminus::error($org . ' is not a member of ' . $site->get('name'));
        }
        $workflow = $member->removeMember('organization', $org);
        $workflow->wait();
        break;
      case 'list':
      default:
        $orgs = $site->org_memberships->all();
        if (empty($orgs)) {
          Terminus::error('No organizations');
        }

        foreach ($orgs as $org) {
          $org_data = $org->get('organization');
          $data[]   = array(
            'label' => $org_data->profile->name,
            'name'  => $org_data->profile->machine_name,
            'role'  => $org->get('role'),
            'id'    => $org->get('organization_id'),
          );
        }
        $this->outputter->outputRecordList($data);
        break;
    }
    if (isset($workflow)) {
      $this->workflowOutput($workflow);
    }
  }

  /**
    * Get, load, create, or list backup information
    *
    * ## OPTIONS
    *
    * <get|load|create|list>
    * : Function to run - get, load, create, or list
    *
    * [--site=<site>]
    * : Site to load
    *
    * [--env=<env>]
    * : Environment to load
    *
    * [--element=<code|files|db|all>]
    * : Element to download or create. *all* only used for 'create'
    *
    * [--to=<directory|file>]
    * : Absolute path of a directory or filename to save the downloaded backup to
    *
    * [--latest]
    * : If set the latest backup will be selected automatically
    *
    * [--keep-for]
    * : Number of days to keep this backup
    *
    * @subcommand backups
    *
    */
  public function backups($args, $assoc_args) {
    $action = array_shift($args);
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $env    = Input::env($assoc_args, 'env');
    //Backward compatability supports "database" as a valid element value.
    if(
      isset($assoc_args['element'])
      && ($assoc_args['element'] == 'database')
    ) {
      $assoc_args['element'] = 'db';
    }

    switch ($action) {
      case 'get':
        if (isset($assoc_args['element'])) {
          $element = $assoc_args['element'];
        } else {
          $element = Terminus::menu(
            array('code', 'files', 'db'),
            null,
            'Select backup element',
            true
          );
        }

        if (!in_array($element,array('code', 'files', 'db'))) {
          Terminus::error('Invalid backup element specified.');
        }
        $latest  = Input::optional('latest', $assoc_args, false);
        $backups = $site->environments->get($env)->backups($element);

        //Ensure that that backups being presented for retrieval have finished
        $backups = array_filter($backups, function($backup) {
          return (isset($backup->finish_time) && $backup->finish_time);
        });

        if ($latest) {
          $backups = array(array_pop($backups));
        }

        if (empty($backups)) {
          \Terminus::error('No backups available.');
        }
        $menu = $folders = array();

        foreach($backups as $folder => $backup) {
          if (!isset($backup->filename)) {
            continue;
          }
          if (!isset($backup->folder)) {
            $backup->folder = $folder;
          }
          $buckets[] = $backup->folder;
          $menu[]    = $backup->filename;
        }

        if (empty($menu)) {
          Terminus::error(
            'No backups available. Create one with '
            . '`terminus site backup create --site=%s --env=%s`',
            array($site->get('name'), $env)
          );
        }

        $index = 0;
        if (!$latest) {
          $index = Terminus::menu($menu, null, 'Select backup');
        }
        $bucket   = $buckets[$index];
        $filename = $menu[$index];

        $url = $site->environments->get($env)->backupUrl($bucket, $element);

        if (isset($assoc_args['to'])) {
          $target = $assoc_args['to'];
          if (is_dir($target)) {
            $filename = \Terminus\Utils\get_filename_from_url($url->url);
            $target = sprintf('%s/%s', $target, $filename);
          }
          Terminus::line('Downloading ... please wait ...');
          if (TerminusCommand::download($url->url, $target)) {
            Terminus::success('Downloaded %s', $target);
            return $target;
          } else {
            Terminus::error('Could not download file');
          }
        }
        $this->outputter->outputValue($url->url, 'Backup URL');
        return $url->url;
        break;
    case 'load':
      $assoc_args['to'] = '/tmp';
      $assoc_args['element'] = 'database';
      if (isset($assoc_args['database'])) {
        $database = $assoc_args['database'];
      } else {
        $database = escapeshellarg(Terminus::prompt('Name of database to import to'));
      }
      if (isset($assoc_args['username'])) {
        $username = $assoc_args['username'];
      } else {
        $username = escapeshellarg(Terminus::prompt('Username'));
      }
        if (isset($assoc_args['password'])) {
          $password = $assoc_args['password'];
        } else {
          $password = escapeshellarg(Terminus::prompt('Password'));
        }

        exec('mysql -e "show databases"', $stdout, $exit);
        if ($exit != 0) {
          Terminus::error('MySQL does not appear to be installed on your server.');
        }

        $assoc_args['env'] = $env;
        $target = $this->backup(array('get'), $assoc_args);
        $target = '/tmp/' . \Terminus\Utils\get_filename_from_url($target);

        if (!file_exists($target)) {
          Terminus::error('Cannot read database file %s', array($target));
        }

        Terminus::line('Unziping database');
        exec("gunzip $target", $stdout, $exit);

        // trim the gz of the target
        $target = Terminus\Utils\sql_from_zip($target);
        $target = escapeshellarg($target);
        exec("mysql $database -u $username -p'$password' < $target", $stdout, $exit);
        if ($exit != 0) {
          Terminus::error('Could not import database');
        }

        Terminus::success('%s successfuly imported to %s', array($target, $database));
        return true;
        break;
      case 'create':
        if (!array_key_exists('element',$assoc_args)) {
          $assoc_args['element'] = Input::menu(array('code', 'db', 'files', 'all'), 'all', 'Select element');
        }
        $workflow = $site->environments->get($env)->createBackup($assoc_args);
        $workflow->wait();
        $this->workflowOutput($workflow);
        break;
      case 'list':
      default:
        $backups = $site->environments->get($env)->backups();
        $element_name = false;
        if (isset($assoc_args['element']) && ($assoc_args['element'] != 'all')) {
          $element_name =  $assoc_args['element'];
        }
        if ($element_name == 'db') {
          $element_name = 'database';
        }

        $data = array();
        foreach ($backups as $id => $backup) {
          if (
            !isset($backup->filename)
            || (
              $element_name 
              && !preg_match(sprintf('/backup_%s/', $element_name), $id)
            )
          ) {
            continue;
          }

          $date = 'Pending';
          if (isset($backup->finish_time)) {
            $date = date('Y-m-d H:i:s', $backup->finish_time);
          }

          $size = $backup->size / 1048576;
          if ($size > 0.1) {
            $size = sprintf('%.1fMB', $size);
          } elseif ($size > 0) {
            $size = '0.1MB';
          } else {
            //0-byte backups should not be recommended for restoration
            $size = 'Incomplete';
          }

          $data[] = array(
            'file' => $backup->filename,
            'size' => $size,
            'date' => $date,
          );
        }

        if (empty($backups)) {
          \Terminus::error('No backups found.');
          return false;
        } else {
          $this->outputter->outputRecordList($data, array('file' => 'File', 'size' => 'Size', 'date' => 'Date'));
          return $data;
        }
      break;
    }
   }

  /**
   * Init dev to test or test to live
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--env]
   * : Environment you want to initialize
   *
   * @subcommand init-env
   */
   public function init_env($args, $assoc_args) {
     $site         = $this->sites->get(Input::sitename($assoc_args));
     $environments = array('dev', 'test', 'live');
     $env          = $site->environments->get(Input::env(
       $assoc_args,
       'env',
       'Choose environment you want to initialize',
       array('test', 'live')
     ));

     if ($env->isInitialized()) {
       Terminus::error(sprintf(
         'The %s environment has already been initialized',
         $env->get('id')
       ));
     }

     $workflow = $env->initializeBindings();
     $workflow->wait();
     $this->workflowOutput($workflow);
     return true;
   }

  /**
   * Overwrites the content (database and/or files) of one environment with
   * content from another environment
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--from-env]
   * : Environment you want to clone from
   *
   * [--to-env]
   * : Environment you want to clone to
   *
   * [--db-only]
   * : Clone only the the database
   *
   * [--files-only]
   * : Clone only the files
   *
   * @subcommand clone-content
   */
  public function clone_content($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $from_env = $site->environments->get(Input::env(
      $assoc_args,
      'from-env',
      'Choose environment you want to clone from'
    ));
    $to_env   = Input::env(
      $assoc_args,
      'to-env',
      'Choose environment you want to clone to'
    );

    $db     = isset($assoc_args['db-only']);
    $files  = isset($assoc_args['files-only']);
    if (!$files && !$db) {
      $files = $db = true;
    }

    $append = array();
    if ($db) {
      $append[] = 'DATABASE';
    }
    if ($files) {
      $append[] = 'FILES';
    }
    $append  = implode(' and ', $append);
    $confirm = sprintf(
      "Are you sure?\n\tClone from %s to %s\n\tInclude: %s\n",
      strtoupper($from_env->getName()),
      strtoupper($to_env),
      $append
    );
    \Terminus::confirm($confirm);

    if ($site->environments->get($to_env) == null) {
      \Terminus::error('The %s environment was not found.', $to_env);
    }

    if ($db) {
      \Terminus::line('Cloning database ... ');
      $workflow = $from_env->cloneDatabase($to_env);
      $workflow->wait();
    }

    if ($files) {
      \Terminus::line('Cloning files ... ');
      $workflow = $from_env->cloneFiles($to_env);
      $workflow->wait();
    }
    if (isset($workflow)) {
      $this->workflowOutput($workflow);
    }
    return true;
  }

  /**
   * Create a MultiDev environment
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--to-env=<env>]
   * : Name of environment to create
   *
   * [--from-env=<env>]
   * : Environment clone content from, default = dev
   *
   * @subcommand create-env
   */
  public function create_env($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    if ((boolean)$site->getFeature('multidev')) {
      if (isset($assoc_args['to-env'])) {
        $env_id = $assoc_args['to-env'];
      } else {
        $env_id = Terminus::prompt('Name of new multidev environment');
      }

      $src = $site->environments->get(
        Input::env(
          $assoc_args,
          'from-env',
          'Environment to clone content from',
          $site->environments->ids()
        )
      );

      $workflow = $site->environments->create($env_id);
      $workflow->wait();
      $this->workflowOutput($workflow);
    } else {
      Terminus::error(
        'This site does not have the authority to conduct this operation.'
      );
    }
  }

   /**
    * Merge a Multidev Environment into Dev Environment
    *
    * ## OPTIONS
    *
    * [--site=<site>]
    * : Site to use
    *
    * [--env=<env>]
    * : Name of multidev to environment to merge into Dev
    *
    * @subcommand merge-to-dev
    */
    public function merge_to_dev($args, $assoc_args) {
      $site = $this->sites->get(Input::sitename($assoc_args));

      $multidev_ids = array_map(function($env) {
        return $env->get('id');}, $site->environments->multidev()
      );
      $multidev_id = Input::env($assoc_args, 'env', "Multidev environment to merge into Dev Environment", $multidev_ids);
      $environment = $site->environments->get($multidev_id);

      $workflow = $environment->mergeToDev();
      $workflow->wait();

      Terminus::success(sprintf('Merged the %s environment into dev', $environment->get('id')));
    }

  /**
   * Merge the Dev Environment (Master) into a Multidev Environment
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--env=<env>]
   * : Name of multidev to environment to merge Dev into
   *
   * @subcommand merge-from-dev
   */
  public function merge_from_dev($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    $multidev_ids = array_map(
      function($env) {return $env->get('id');},
      $site->environments->multidev()
    );
    $multidev_id = Input::env(
      $assoc_args,
      'env',
      'Multidev environment that the Dev Environment will be merged into',
      $multidev_ids
    );
    $environment = $site->environments->get($multidev_id);

    $workflow = $environment->mergeFromDev();
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Delete a git branch from site remote
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--branch=<branch>]
   * : name of branch to delete
   *
   * @subcommand delete-branch
   */
  public function delete_branch($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $multidev_envs = array_diff(
      $site->environments->ids(),
      array('dev', 'test', 'live')
    );
    $branch = Input::env(
      $assoc_args,
      'branch',
      'Branch to delete',
      $multidev_envs
    );

    Terminus::confirm(
      sprintf(
        'Are you sure you want to delete the "%s" branch from %s?',
        $branch,
        $site->get('name')
      )
    );

    $workflow = $site->deleteBranch($branch);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

   /**
    * Delete a multidev environment
    *
    * ## OPTIONS
    *
    * [--site=<site>]
    * : Site to use
    *
    * [--env=<env>]
    * : name of environment to delete
    *
    * [--remove-branch]
    * : delete branch corresponding to env
    *
    * @subcommand delete-env
    */
  public function delete_env($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $multidev_envs = array_diff(
      $site->environments->ids(),
      array('dev', 'test', 'live')
    );
    $env = Input::env(
      $assoc_args,
      'env',
      'Environment to delete',
      $multidev_envs
    );
    $delete_branch = false;
    if(isset($assoc_args['remove_branch'])) {
      $delete_branch = (boolean)$assoc_args['remove_branch'];
    }

    Terminus::confirm(
      sprintf(
        'Are you sure you want to delete the "%s" environment from %s?',
        $env,
        $site->get('name')
      )
    );

    $workflow = $site->deleteEnvironment($env, $delete_branch);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

   /**
    * Deploy dev environment to test or live
    *
    * ## OPTIONS
    *
    * [--site=<site>]
    * : Site to deploy from
    *
    * [--env=<test|live>]
    * : Environment to deploy to (Test or Live)
    *
    * [--sync-content]
    * : If deploying test, copy database and files from Live
    *
    * [--cc]
    * : Clear cache after deploy?
    *
    * [--updatedb]
    * : (Drupal only) run update.php after deploy
    *
    * [--note=<note>]
    * : deploy log message
    *
    */
  public function deploy($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(Input::env(
      $assoc_args,
      'env',
      'Choose environment to deploy to',
      array('test', 'live')
    ));

    if (!$env || !in_array($env->get('id'), array('test', 'live'))) {
      Terminus::failure('You can only deploy to the test or live environment.');
    }

    $sync_content = ($env->get('id') == 'test' && isset($assoc_args['sync-content']));

    if(!isset($assoc_args['note'])) {
      $annotation = Terminus::prompt(
        'Custom note for the deploy log',
        array(),
        'Deploy from Terminus 2.0'
      );
    } else {
      $annotation = $assoc_args['note'];
    }

    $cc       = (integer)array_key_exists('cc', $assoc_args);
    $updatedb = (integer)array_key_exists('updatedb', $assoc_args);

    $params = array(
      'updatedb'       => $updatedb,
      'clear_cache'    => $cc,
      'annotation'     => $annotation,
    );

    if ($sync_content) {
      $params['clone_database'] = array('from_environment' => 'live');
      $params['clone_files']    = array('from_environment' => 'live');
    }

    $workflow = $env->deploy($params);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * List environments for a site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Name of site to check
   *
   */
  public function environments($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $environments = $site->environments->all();

    $data = array();
    foreach ($environments as $name => $env) {
      $osd  = $locked = 'false';
      $lock = $env->get('lock');
      if ((boolean)$lock->locked) {
        $locked = 'true';
      }
      if ((boolean)$env->get('on_server_development')) {
        $osd = 'true';
      }

      $data[] = array(
        'name'          => $env->get('id'),
        'created'       => date('Y-m-dTH:i:s', $env->get('environment_created')),
        'domain'        => $env->domain(),
        'onserverdev'   => $osd,
        'locked'        => $locked,
      );
    }
    $this->outputter->outputRecordList($data, ['name' => 'Name', 'created' => 'Created', 'domain' => 'Domain', 'onserverdev' => 'OnServer Dev?', 'locked' => 'Locked?']);
    return $data;
  }

  /**
   * Hostname operations
   *
   * ## OPTIONS
   *
   * <list|add|remove>
   * : OPTIONS are list, add, delete
   *
   * [--site=<site>]
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
     $site   = $this->sites->get(Input::sitename($assoc_args));
     $env    = $site->environments->get(Input::env($assoc_args, 'env'));
     switch ($action) {
       case 'list':
        $hostnames = $env->getHostnames();
        $data      = $hostnames;
        if (!Terminus::get_config('json')) {
          //If were not just dumping the JSON, then we should reformat the data.
          $data = array();
          foreach ($hostnames as $hostname => $details) {
            $data[] = array_merge(
              array('domain' => $hostname),
              (array)$details
            );
          }
        }
         $this->outputter->outputRecordList($data);
        break;
       case 'add':
          if (!isset($assoc_args['hostname'])) {
            Terminus::error('Must specify hostname with --hostname');
          }
          $data = $env->addHostname($assoc_args['hostname']);
          if (Terminus::get_config('verbose')) {
            \Terminus\Utils\json_dump($data);
          }
          Terminus::success(
            'Added %s to %s-%s',
            array($assoc_args['hostname'], $site->get('name'), $env->get('id'))
          );
          break;
       case 'remove':
          if (!isset($assoc_args['hostname'])) {
            Terminus::error('Must specify hostname with --hostname');
          }
          $data = $env->deleteHostname($assoc_args['hostname']);
          Terminus::success(
            'Deleted %s from %s-%s',
            array( $assoc_args['hostname'], $site->get('name'), $env->get('id'))
          );
          break;
     }
     return $data;
   }

  /**
   * Lock an environment to prevent changes
   *
   * ## OPTIONS
   *
   * <info|add|remove>
   * : action to execute ( i.e. info, add, remove )
   *
   * [--site=<site>]
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
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $env    = $site->environments->get(Input::env($assoc_args, 'env'));
    switch ($action) {
      case 'info':
        $info = $env->lockinfo();
        $this->outputter->outputRecord($info);
        break;
      case 'add':
        Terminus::line(
          'Creating new lock on %s -> %s',
          array($site->get('name'), $env->get('id'))
        );
        if (!isset($assoc_args['username'])) {
          $username = Terminus::prompt('Username for the lock');
        } else {
          $username = $assoc_args['username'];
        }
        if (!isset($assoc_args['password'])) {
          exec('stty -echo');
          $password = Terminus::prompt('Password for the lock');
          exec('stty echo');
          Terminus::line();
        } else {
          $password = $assoc_args['password'];
        }

        $workflow = $env->lock(
          array(
            'username' => $username,
            'password' => $password
          )
        );
        $workflow->wait();
        break;
      case 'remove':
        Terminus::line(
          'Removing lock from %s -> %s',
          array($site->get('name'), $env->get('id'))
        );
        $workflow = $env->unlock();
        $workflow->wait();
        break;
    }
    if (isset($workflow)) {
      $this->workflowOutput($workflow);
    }
  }

  /**
   * Import a zip archive == see this article for more info:
   * http://helpdesk.getpantheon.com/customer/portal/articles/1458058-importing-a-wordpress-site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--url=<url>]
   * : URL of archive to import
   *
   * [--element=<element>]
   * : Site element to import (i.e. code, files, db, or all)
   *
   * @subcommand import
   */
  public function import($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $url   = Input::string($assoc_args, 'url', 'URL of archive to import');
    if (!$url) {
      Terminus::error('Please enter a URL.');
    }

    if(!isset($assoc_args['element'])) {
      $element_options = array('code', 'database', 'files', 'all');
      $element_key     = Input::menu(
        $element_options,
        'all',
        'Which element are you importing?'
      );
      $element         = $element_options[$element_key];
    } else {
      $element = $assoc_args['element'];
    }

    $workflow = $site->import($url, $element);
    Terminus::line(
      'Import started, '
      . 'you can now safely kill this script without interfering.'
    );
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Change the site payment instrument
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--instrument=<UUID>]
   * : Change the instrument by setting the ID
   *
   * @subcommand set-instrument
   *
   * ## EXAMPLES
   *
   *  terminus site set-instrument --site=sitename
   */
  public function set_instrument($args, $assoc_args) {
    $user        = new User();
    $instruments = $user->instruments->getMemberList('id', 'label');
    if (!isset($assoc_args['instrument'])) {
      $instrument_id = Input::menu(
        $instruments,
        null,
        'Select a payment instrument'
      );
    } else {
      $instrument_id = $assoc_args['instrument'];
    }

    if (
      !isset($instruments[$instrument_id])
      && !in_array($instrument_id, Input::$NULL_INPUTS)
    ) {
      $this->logger->error("You do not have permission to attach instrument $instrument_id");
      die(-1);
    }

    $site = $this->sites->get(Input::sitename($assoc_args));
    if ($instrument_id == 0) {
      $workflow = $site->removeInstrument();
    } else {
      $workflow = $site->addInstrument($instrument_id);
    }
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Mount a site with sshfs
   *
   * ## OPTIONS
   *
   * [--site=<site>]
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
    exec('which sshfs', $stdout, $exit);
    if ($exit !== 0) {
      Terminus::error('Must install sshfs first');
    }

    $destination = \Terminus\Utils\destination_is_valid($assoc_args['destination']);

    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = Input::env($assoc_args, 'env');

    exec('uname', $output, $ret);
    $darwin = '';
    if (
      is_array($output)
      && isset($output[0])
      && strpos($output[0], 'Darwin') !== false
    ) {
      $darwin = '-o defer_permissions ';
    }
    $user = $env . '.' . $site->get('id');
    $host = sprintf(
      'appserver.%s.%s.drush.in',
      $env,
      $site->get('id')
    );
    $cmd = sprintf(
      'sshfs %s -p 2222 %s@%s:./ %s',
      $darwin,
      $user,
      $host,
      $destination
    );
    exec($cmd, $stdout, $exit);
    if ($exit != 0) {
      Terminus::error("Couldn't mount $destination");
    }
    Terminus::success(
      'Site mounted to %s. To unmount, run: umount %s (or fusermount -u %s).',
      array($destination, $destination, $destination)
    );
  }

  /**
  * Get New Relic Info for site
  *
  * ## OPTIONS
  *
  * [--site=<site>]
  * : site for which to retreive notifications
  *
  * @subcommand new-relic
  */
  public function new_relic($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $data = $site->newRelic();
    if ($data) {
      $this->outputter->outputRecord($data->account, array('Key', 'Value'));
    } else {
      Logger::coloredOutput('%YNew Relic is not enabled.%n');
    }
  }

  /**
  * Get the site owner
  *
  * ## OPTIONS
  *
  * [--site=<site>]
  * : Site to check
  *
  * @subcommand owner
  */
  public function owner($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $this->outputter->outputValue($site->get('owner'), 'Site Owner');
  }

  /**
   * Set the site owner
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to check
   *
   * [--set=<value>]
   * : new owner to set
   *
   * @subcommand set-owner
   */
  public function set_owner($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $workflow = $site->setOwner($assoc_args['set']);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Interacts with redis
   *
   * ## OPTIONS
   *
   * <clear>
   * : clear - Clear redis cache on remote server
   *
   * [--site=<site>]
   * : site name
   *
   * [--env=<env>]
   * : environment
   *
   * ## Examples
   *
   *    terminus site redis clear --site=mikes-wp-test --env=live
   *
   */
  public function redis($args, $assoc_args) {
    $action = array_shift($args);
    $site = $this->sites->get(Input::sitename($assoc_args));
    if (isset($assoc_args['env'])) {
      $env = $assoc_args['env'];
    }
    switch ($action) {
      case 'clear':
        $bindings = $site->bindings('cacheserver');
        if (empty($bindings)) {
          \Terminus::error('Redis cache not enabled');
        }
        $commands = array();
        foreach ($bindings as $binding) {
          if (isset($env) && (boolean)$env && $env != $binding->environment) {
            continue;
          }
          $args = array(
            $binding->environment,
            $site->get('id'),
            $binding->environment,
            $site->get('id'),
            $binding->host,
            $binding->port,
            $binding->password
          );
          array_filter($args, function($a) {return escapeshellarg($a);});
          $commands[$binding->environment] = vsprintf(
            'ssh -p 2222 %s.%s@appserver.%s.%s.drush.in "redis-cli -h %s -p %s -a %s flushall"',
            $args
          );
        }
        foreach ($commands as $env => $command) {
          Terminus::line('Clearing redis on %s r ', array($env));
          exec($command, $stdout, $return);
          Terminus::success($stdout[0]);
        }
        break;
    }
  }

  /**
   * Get or set service level
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to check
   *
   * [--level=<value>]
   * : new service level to set
   *
   * @subcommand set-service-level
   */
  public function set_service_level($args, $assoc_args) {
    $site  = $this->sites->get(Input::sitename($assoc_args));
    $info  = $site->get('service_level');
    $level = $assoc_args['level'];
    $data  = $site->updateServiceLevel($level);
    $this->logger->info("Service level has been updated to '$level'");
  }

  /**
   * Manage site organization tags
   *
   * ## OPTIONS
   *
   * <add|remove|list>
   * : subfunction to run
   *
   * [--site=<site>]
   * : Site's name
   *
   * [--org=<name|id>]
   * : Organization to apply tag with
   *
   * [--tag=<tag>]
   * : Tag to add or remove
   *
   * @subcommand tags
   */
  public function tags($args, $assoc_args) {
    $action  = array_shift($args);
    $site    = $this->sites->get(Input::sitename($assoc_args));
    $org     = Input::orgid($assoc_args, 'org');
    if ($site->organizationIsMember($org)) {
      $data = array();
      switch ($action) {
        case 'add':
          $verb     = 'added to';
          $tag      = Input::string($assoc_args, 'tag', 'Enter a tag to add');
          $response = $site->addTag($tag, $org);
          break;
        case 'remove':
          $verb    = 'removed from';
          $tags   = $site->getTags($org);
          if (count($tags) === 0) {
            Terminus::error(
              'This organization does not have any tags'
              . ' associated with this site.'
            );
          } elseif (
            !isset($assoc_args['tag'])
            || !in_array($assoc_args['tag'], $tags)
          ) {
            $tag = $tags[Input::menu($tags, null, 'Select a tag to delete')];
          } else {
            $tag = $assoc_args['tag'];
          }
          $response = $site->removeTag($tag, $org);
          break;
        case 'list':
        default:
          $tags = $site->getTags($org);
          $this->outputter->outputRecord(array('tags' => $tags));
          break;
      }
      if (in_array($action, array('add', 'remove'))) {
        $message  = 'Tag %s %s %s %s';
        $messages = array(
          'success' => sprintf(
            $message,
            '"' . $tag . '"',
            'has been',
            $verb,
            $site->get('name')
          ),
          'failure' => sprintf(
            $message,
            '"' . $tag . '"',
            'could not be',
            $verb,
            $site->get('name')
          )
        );
        $this->responseOutput($response, $messages);
      }
    } else {
      Terminus::error(
        $site->get('name') . ' is not a member of an organization, '
        . 'which is necessary to associate a tag with a site.'
      );
    }
  }

  /**
  * Get or set team members
  *
  * ## OPTIONS
  *
  * <list|add-member|remove-member|change-role>
  * : i.e. add or remove
  *
  * [--site=<site>]
  * : Site to check
  *
  * [--member=<email>]
  * : Email of the member to add. Member will receive an invite
  *
  * [--role=<role>]
  * : Role for the new member to act as
  *
  * @subcommand team
  */
  public function team($args, $assoc_args) {
    $action = array_shift($args) ?: 'list';
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $data   = array();
    $team   = $site->user_memberships;
    switch($action) {
      case 'add-member':
        if((boolean)$site->getFeature('change_management')) {
          $role = Input::role($assoc_args);
        } else {
          $role = 'team_member';
        }
        $workflow = $team->addMember($assoc_args['member'], $role);
        $this->workflowOutput($workflow);
        break;
      case 'remove-member':
        $user     = $team->get($assoc_args['member']);
        if ($user != null) {
          $workflow = $user->removeMember($assoc_args['member']);
          $this->workflowOutput($workflow);
        } else {
          Terminus::error(
            '"' . $assoc_args['member'] . '" is not a valid member.'
          );
        }
          break;
      case 'change-role':
        if((boolean)$site->getFeature('change_management')) {
          $role = Input::role($assoc_args);
          $user = $team->get($assoc_args['member']);
          if ($user != null) {
            $workflow = $user->setRole($role);
            $this->workflowOutput($workflow);
          } else {
            Terminus::error(
              '"' . $assoc_args['member'] . '" is not a valid member.'
            );
          }
        } else {
          Logger::redline(
            'This site does not have the authority to conduct this operation.'
          );
        }
        break;
      case 'list':
      default:
        $user_memberships = $team->all();
        foreach($user_memberships as $uuid => $user_membership) {
          $user = $user_membership->get('user');
          $data[] = array(
            'First' => $user->profile->firstname,
            'Last'  => $user->profile->lastname,
            'Email' => $user->email,
            'UUID'  => $user->id,
          );
        }
        ksort($data);
        break;
    }
    if(!empty($data)) {
      $this->outputter->outputRecordList($data);
    }
  }

  /**
   * Show upstream updates
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to check
   *
   * @subcommand upstream-info
   */
  public function upstream_info($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $upstream = $site->get('upstream');
    $this->outputter->outputRecord($upstream);
  }

  /**
   * Show or apply upstream updates
   *
   * ## OPTIONS
   *
   * [<list|apply>]
   * : Are we inspecting or applying upstreams?
   *
   * [--site=<site>]
   * : Site to check
   *
   * [--env=<name>]
   * : Environment (dev or multidev) to apply updates to; Default: dev
   *
   * [--accept-upstream]
   * : Attempt to automatically resolve conflicts in favor of the upstream repository.
   *
   * [--updatedb]
   * : (Drupal only) run update.php after updating,
   *
   * @subcommand upstream-updates
   */
  public function upstream_updates($args, $assoc_args) {
    $action   = array_shift($args) ?: 'list';
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $upstream = $site->getUpstreamUpdates();

    switch($action) {
      case 'list':
        $data = array();
        if(isset($upstream->remote_url) && isset($upstream->behind)) {
          $data[$upstream->remote_url] = 'Up-to-date';
          if ($upstream->behind > 0) {
            $data[$upstream->remote_url] = 'Updates Available';
          }

          $this->constructTableForResponse($data, array('Upstream', 'Status'));
          if (!isset($upstream) || empty($upstream->update_log)) {
            Terminus::success('No updates to show');
          }
          $upstreams = (array)$upstream->update_log;
          if (!empty($upstreams)) {
            $data = array();
            foreach ($upstreams as $commit) {
              $data[] = array(
                'hash'     => $commit->hash,
                'datetime' => $commit->datetime,
                'message'  => $commit->message,
                'author'   => $commit->author,
              );
            }
          }
        } else {
          Terminus::warning(
            'There was a problem checking your upstream status. Please try again.'
          );
        }
        $this->outputter->outputRecordList($data);
        break;
      case 'apply':
        if (!empty($upstream->update_log)) {
          $env = 'dev';
          if (isset($assoc_args['env'])) {
            $env = $assoc_args['env'];
          }
          if (in_array($env, array('test', 'live'))) {
            Terminus::error(
              sprintf(
                'Upstream updates cannot be applied to the %s environment',
                $env
              )
            );
          }

          $updatedb = (isset($assoc_args['updatedb']) && $assoc_args['updatedb']);
          $acceptupstream = (isset($assoc_args['accept-upstream']) && $assoc_args['accept-upstream']);
          Terminus::confirm(
            sprintf(
              'Are you sure you want to apply the upstream updates to %s-dev',
              $site->get('name'),
              $env
            )
          );
          $workflow = $site->applyUpstreamUpdates($env, $updatedb, $acceptupstream);
          $workflow->wait();
          $this->workflowOutput($workflow);
        }
        else {
          Terminus::warning(
            'There are no upstream updates to apply.'
          );
        }
        break;
    }

  }

  /**
   * Pings a site to ensure it responds
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : site to ping
   *
   * [--env=<env>]
   * : environment to ping
   *
   * ## Examples
   *  terminus site wake --site='testsite' --env=dev
  */
  public function wake($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = Input::env($assoc_args, 'env');
    $data = $site->environments->get($env)->wake();
    if (!$data['success']) {
      Logger::redLine(sprintf('Could not reach %s', $data['target']));
      return;
    }
    if (!$data['styx']) {
      Terminus::error('Pantheon headers missing, which is not quite right.');
    }

    Terminus::success(
      sprintf('OK >> %s responded in %s', $data['target'], $data['time'])
    );
  }

  /**
   * Complete wipe and reset a site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--env=<env>]
   * : Environment to be wiped
   */
  public function wipe($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(Input::env($assoc_args, 'env'));
    Terminus::confirm(
      sprintf(
        'Are you sure you want to wipe %s - %s?',
        $site->get('name'),
        $env->get('id')
      )
    );

    $workflow = $env->wipe();
    $workflow->wait();
    Terminus::success(
      sprintf(
        'Successfully wiped %s - %s',
        $site->get('name'),
        $env->get('id')
      )
    );
  }

  /**
   * List a site's workflows
   *
   * ## OPTIONS
   * [--site=<site>]
   * : Site to check
   *
   * @subcommand workflows
   */
  public function workflows($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $workflows = $site->workflows->all();
    $data = array();
    foreach($workflows as $workflow) {
      $user = 'Pantheon';
      if (isset($workflow->get('user')->email)) {
        $user = $workflow->get('user')->email;
      }
      $data[] = array(
        'workflow'       => $workflow->get('description'),
        'user'           => $user,
        'status'         => $workflow->get('phase'),
        'last_update'    => date(
          'Y-m-dTH:i:s',
          ($workflow->get('created_at') + $workflow->get('total_time'))
        ),
        'tasks/complete' => 
          $workflow->get('step') . '/' . $workflow->get('number_of_tasks'),
      );
    }
    if (count($data) > 0) {
      $this->constructTableForResponse($data);
    } else {
      Terminus::error('No workflows have been run on ' . $site->getName());
    }
  }

  /**
   * Checks to ensure user can access the given organization
   *
   * @param [string] $org_id Organization name or UUID
   * @return [boolean] $is_ok True if this organization is accessible
   */
  private function isOrgAccessible($org_id) {
    $user  = new User();
    $org   = $user->organizations->get($org_id);
    $is_ok = is_object($org);
    return $is_ok;
  }

}

\Terminus::add_command('site', 'Site_Command');
