<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Auth;
use Terminus\Request;
use Terminus\Utils;
use Terminus\Commands\TerminusCommand;
use Terminus\Helpers\Input;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\User;
use Terminus\Models\Collections\Sites;

/**
* Actions to be taken on an individual site
*/
class SiteCommand extends TerminusCommand {

  protected $_headers = false;

  public function __construct() {
    Auth::ensureLogin();
    parent::__construct();
    $this->sites = new Sites();
  }

  /**
   * Get, load, create, or list backup information
   *
   * ## OPTIONS
   *
   * <get|load|create|list|get-schedule|set-schedule|cancel-schedule>
   * : Function to run - get, load, create, list, get schedule, set schedule,
   *   or cancel schedule
   *
   * [--site=<site>]
   * : Site to load
   *
   * [--env=<env>]
   * : Environment to load
   *
   * [--element=<code|files|db|all>]
   * : Element to download or create. `all` is only used for 'create'
   *
   * [--to=<directory|file>]
   * : Absolute path of a directory or filename to save the downloaded backup to
   *
   * [--file=<filename>]
   * : Select one of the files from the list subcommand. Only used for 'get'
   *
   * [--latest]
   * : If set the latest backup will be selected automatically
   *
   * [--keep-for]
   * : Number of days to keep this backup
   *
   * [--day]
   * : Day of the week on which to run weekly backups
   *
   * @subcommand backups
   *
   */
  public function backups($args, $assoc_args) {
    $action = array_shift($args);

    switch ($action) {
      case 'get-schedule':
        $this->showBackupSchedule($assoc_args);
          break;
      case 'set-schedule':
        $this->setBackupSchedule($assoc_args);
          break;
      case 'cancel-schedule':
        $this->cancelBackupSchedule($assoc_args);
          break;
      case 'get':
        $url = $this->getBackup($assoc_args);
        $this->output()->outputValue($url, 'Backup URL');
          break;
      case 'load':
        $this->loadBackup($assoc_args);
          break;
      case 'create':
        $workflow = $this->createBackup($assoc_args);
        $workflow->wait();
        $this->workflowOutput($workflow);
          break;
      case 'list':
      default:
        $data = $this->listBackups($assoc_args);
        $this->output()->outputRecordList(
          $data,
          array('file' => 'File', 'size' => 'Size', 'date' => 'Date')
        );
          return $data;
          break;
    }
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
  public function clearCache($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $env_id   = Input::env(array('args' => $assoc_args, 'site' => $site));
    $workflow = $site->workflows->create(
      'clear_cache',
      array('environment' => $env_id)
    );
    $workflow->wait();
    $this->workflowOutput($workflow);
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
  public function cloneContent($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $from_env = $site->environments->get(
      Input::env(
        array(
          'args'  => $assoc_args,
          'key'   => 'from-env',
          'label' => 'Choose environment you want to clone from',
          'site'  => $site,
        )
      )
    );
    $to_env   = Input::env(
      array(
        'args'  => $assoc_args,
        'key'   => 'to-env',
        'label' => 'Choose environment you want to clone to',
        'site'  => $site,
      )
    );

    $db    = isset($assoc_args['db-only']);
    $files = isset($assoc_args['files-only']);
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
    Terminus::confirm($confirm);

    if ($site->environments->get($to_env) == null) {
      $this->failure(
        'The {env} environment was not found.',
        array('env' => $to_env)
      );
    }

    if ($db) {
      $this->log()->info('Cloning database ... ');
      $workflow = $from_env->cloneDatabase($to_env);
      $workflow->wait();
    }

    if ($files) {
      $this->log()->info('Cloning files ... ');
      $workflow = $from_env->cloneFiles($to_env);
      $workflow->wait();
    }
    if (isset($workflow)) {
      $this->workflowOutput($workflow);
    }
    return true;
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
    $site       = $this->sites->get(Input::sitename($assoc_args));
    $data       = $headers = array();
    $env        = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );
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
        $data    = $site->tips();
        $headers = array('Branch', 'Commit');
          break;
      case 'commit':
        $diff    = $env->diffstat();
        $count   = count((array)$diff);
        $message = "Commit changes to $count files?";
        if ($count === 0) {
          $message = 'There are no changed files. Commit anyway?';
          Terminus::confirm($message);
        }
        $message  = Input::string(
          $assoc_args,
          'message',
          'Please enter a commit message.',
          'Terminus commit.'
        );
        $workflow = $env->commitChanges($message);
        $workflow->wait();
        $this->workflowOutput($workflow);
          return true;
          break;
      case 'diffstat':
        $diff = (array)$env->diffstat();
        if (empty($diff)) {
          $this->log()->info('No changes on server.');
          return true;
        }
        $data   = array();
        $filter = false;
        if (isset($assoc_args['filter'])) {
          $filter = $assoc_args['filter'];
        }
        foreach ($diff as $file => $stats) {
          if ($filter) {
            $filter = preg_quote($filter, '/');
            $regex  = '/' . $filter . '/';
            if (!preg_match($regex, $file)) {
              continue;
            }
          }
          $data[] = array_merge(array('file' => $file), (array)$stats);
        }
          break;
    }

    if (!empty($data)) {
      $this->output()->outputRecord($data, $headers);
    }
    return $data;
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
   */
  public function connectionInfo($args, $assoc_args) {
    $site        = $this->sites->get(Input::sitename($assoc_args));
    $env_id      = Input::env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env_id);
    $info        = $environment->connectionInfo();

    if (isset($assoc_args['field'])) {
      $field = $assoc_args['field'];
      $this->output()->outputValue($info[$field]);
    } else {
      $this->output()->outputRecord($info);
    }
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
  public function createEnv($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    if ((boolean)$site->getFeature('multidev')) {
      if (isset($assoc_args['to-env'])) {
        $to_env_id = $assoc_args['to-env'];
      } else {
        $to_env_id = Terminus::prompt('Name of new multidev environment');
      }

      $from_env = $site->environments->get(
        Input::env(
          array(
            'args' => $assoc_args,
            'key' => 'from-env',
            'label' => 'Environment to clone content from',
            'site' => $site,
          )
        )
      );

      $workflow = $site->environments->create($to_env_id, $from_env);
      $workflow->wait();
      $this->workflowOutput($workflow);
    } else {
      $this->failure(
        'This site does not have the authority to conduct this operation.'
      );
    }
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
      $this->output()->outputValue($url, 'Dashboard URL');
    } else {
      Terminus::confirm(
        'Do you want to open your dashboard link in a web browser?'
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

    if (!isset($assoc_args['force']) && (!Terminus::getConfig('yes'))) {
      //If the force option isn't used, we'll ask you some annoying questions
      Terminus::confirm(
        sprintf('Are you sure you want to delete %s?', $site->get('name'))
      );
      Terminus::confirm('Are you really sure?');
    }
    $this->log()->info(
      'Deleting {site} ...',
      array('site' => $site->get('name'))
    );
    $response = $site->delete();
    $site->deleteFromCache();

    $this->log()->info('Deleted {site}!', array('site' => $site->get('name')));
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
  public function deleteBranch($args, $assoc_args) {
    $site          = $this->sites->get(Input::sitename($assoc_args));
    $multidev_envs = array_diff(
      $site->environments->ids(),
      array('dev', 'test', 'live')
    );
    $branch        = Input::env(
      array(
        'args'    => $assoc_args,
        'key'     => 'branch',
        'label'   => 'Branch to delete',
        'choices' => $multidev_envs,
      )
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
  public function deleteEnv($args, $assoc_args) {
    $site          = $this->sites->get(Input::sitename($assoc_args));
    $multidev_envs = array_diff(
      $site->environments->ids(),
      array('dev', 'test', 'live')
    );
    $env = Input::env(
      array(
        'args'    => $assoc_args,
        'label'   => 'Environment to delete',
        'choices' => $multidev_envs
      )
    );
    $delete_branch = false;
    if (isset($assoc_args['remove_branch'])) {
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
    $env  = $site->environments->get(
      Input::env(
        array(
          'args' => $assoc_args,
          'label' => 'Choose environment to deploy to',
          'choices' => array('test', 'live'),
        )
      )
    );

    if (!$env || !in_array($env->get('id'), array('test', 'live'))) {
      $this->failure('You can only deploy to the test or live environment.');
    }

    $sync_content = (
      $env->get('id') == 'test'
      && isset($assoc_args['sync-content'])
    );

    if (!isset($assoc_args['note'])) {
      $annotation = Terminus::prompt(
        'Custom note for the deploy log',
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
   * Shows environment information for a site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of the site to get info on
   *
   * [--env=<env>]
   * : name of environment of <site> to get info on
   *
   * [--field=<field>]
   * : field to return
   *
   * @subcommand environment-info
   */
  public function environmentInfo($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );

    if (isset($assoc_args['field'])) {
      $field = $assoc_args['field'];
      $this->output()->outputValue($env->info($field), $field);
    } else {
      $this->output()->outputRecord($env->info());
    }
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
    $site         = $this->sites->get(Input::sitename($assoc_args));
    $environments = $site->environments->all();

    $data = array();
    foreach ($environments as $name => $env) {
      $osd  = $locked = $initialized ='false';
      $lock = $env->get('lock');
      if ((boolean)$lock->locked) {
        $locked = 'true';
      }
      if ((boolean)$env->get('on_server_development')) {
        $osd = 'true';
      }
      if ((boolean)$env->isInitialized()) {
        $initialized = 'true';
      }

      $data[] = array(
        'name'        => $env->get('id'),
        'created'     => date('Y-m-dTH:i:s', $env->get('environment_created')),
        'domain'      => $env->domain(),
        'onserverdev' => $osd,
        'locked'      => $locked,
        'initialized' => $initialized,
      );
    }
    $this->output()->outputRecordList(
      $data,
      array(
        'name'        => 'Name',
        'created'     => 'Created',
        'domain'      => 'Domain',
        'onserverdev' => 'OnServer Dev?',
        'locked'      => 'Locked?',
        'initialized' => 'Initialized?'
      )
    );
    return $data;
  }

  /**
   * Hostname operations
   *
   * ## OPTIONS
   *
   * <list|add|remove|lookup>
   * : OPTIONS are list, add, delete, and lookup
   *
   * [--site=<site>]
   * : Site to use
   *
   * [--env=<env>]
   * : environment to use
   *
   * [--hostname=<hostname>]
   * : hostname to add
   *
   */
  public function hostnames($args, $assoc_args) {
    $action = array_shift($args);
    if ($action != 'lookup') {
      $site   = $this->sites->get(Input::sitename($assoc_args));
      $env    = $site->environments->get(
        Input::env(array('args' => $assoc_args, 'site' => $site))
      );
    }

    switch ($action) {
      case 'add':
        if (!isset($assoc_args['hostname'])) {
          $this->failure('Must specify hostname with --hostname');
        }
        $data = $env->addHostname($assoc_args['hostname']);
        $this->log()->debug(json_encode($data));
        $this->log()->info(
          'Added {hostname} to {site}-{env}',
          array(
            'hostname' => $assoc_args['hostname'],
            'site' => $site->get('name'),
            'env' => $env->get('id')
          )
        );
          break;
      case 'remove':
        if (!isset($assoc_args['hostname'])) {
          $this->failure('Must specify hostname with --hostname');
        }
        $data = $env->deleteHostname($assoc_args['hostname']);
        $this->log()->info(
          'Deleted {hostname} from {site}-{env}',
          array(
            'hostname' => $assoc_args['hostname'],
            'site' => $site->get('name'),
            'env' => $env->get('id')
          )
        );
          break;
      case 'lookup':
        $this->log()->warning('This operation may take a long time to run.');
        $hostname  = Input::string(
          $assoc_args,
          'hostname',
          'Please enter a hostname to look up.'
        );
        $sites    = $this->sites->all();
        $data     = null;
        foreach ($sites as $site_id => $site) {
          $environments = array('dev', 'test', 'live');
          foreach ($environments as $env_name) {
            $environment = $site->environments->get($env_name);
            $hostnames   = array_keys((array)$environment->getHostnames());
            if (in_array($hostname, $hostnames)) {
              $data = array(
                array(
                  'site' => $site->get('name'),
                  'environment' => $environment->get('id')
                )
              );
              break 2;
            }
          }
        }
        if (is_null($data)) {
          $this->log()->info(
            'Could not locate an environment with the hostname "{hostname}".',
            compact('hostname')
          );
        }
        $this->output()->outputRecordList($data);
          break;
      default:
      case 'list':
        $hostnames = $env->getHostnames();
        $data      = $hostnames;
        if (Terminus::getConfig('format') != 'json') {
          //If were not just dumping the JSON, then we should reformat the data.
          $data = array();
          foreach ($hostnames as $hostname => $details) {
            $data[] = array_merge(
              array('domain' => $hostname),
              (array)$details
            );
          }
        }
        $this->output()->outputRecordList($data);
          break;
    }
    return $data;
  }

  /**
   * Import a zip archive == see this article for more info:
   * http://helpdesk.getpantheon.com/customer/portal/articles/...
   *   ...1458058-importing-a-wordpress-site
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
   * : Site element to import (i.e. code or database)
   *
   * @subcommand import-content
   */
  public function import($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $url  = Input::string($assoc_args, 'url', 'URL of archive to import');
    if (!$url) {
      $this->logger->error('Please enter a URL.');
    }

    if (!isset($assoc_args['element'])) {
      $element_options = array('database', 'files');
      $element_key     = Input::menu(
        $element_options,
        null,
        'Which element are you importing?'
      );
      $element         = $element_options[$element_key];
    } else {
      $element = $assoc_args['element'];
    }

    switch ($element) {
      case 'database':
        $workflow = $site->importDatabase($url);
          break;
      case 'files':
        $workflow = $site->importFiles($url);
          break;
    }
    $workflow->wait();
    $this->workflowOutput($workflow);
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
   */
  public function info($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    // Fetch environment data for sftp/git connection info
    $site->environments->all();

    if (isset($assoc_args['field'])) {
      $field = $assoc_args['field'];
      $this->output()->outputValue($site->info($field), $field);
    } else {
      $this->output()->outputRecord($site->attributes);
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
  public function initEnv($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(
      Input::env(
        array('args' => $assoc_args, 'choices' => array('test', 'live'))
      )
    );

    if ($env->isInitialized()) {
      $this->log()->warning(
        'The {env} environment has already been initialized',
        array('env' => $env->get('id'))
      );
      return;
    }

    $workflow = $env->initializeBindings();
    $workflow->wait();
    $this->workflowOutput($workflow);
    return true;
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
   */
  public function lock($args, $assoc_args) {
    $action = array_shift($args);
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $env    = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );
    switch ($action) {
      case 'info':
        $info = $env->lockinfo();
        $this->output()->outputRecord($info);
          break;
      case 'add':
        $this->log()->info(
          'Creating new lock on {site}-{env}',
          array('site' => $site->get('name'), 'env' => $env->get('id'))
        );
        if (!isset($assoc_args['username'])) {
          $username = Terminus::prompt('Username for the lock');
        } else {
          $username = $assoc_args['username'];
        }
        if (!isset($assoc_args['password'])) {
          $password = Terminus::promptSecret('Password for the lock');
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
        $this->log()->info(
          'Removing lock from {site}-{env}',
          array('site' => $site->get('name'), 'env' => $env->get('id'))
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
   * Merge the dev environment (i.e. master) into a multidev environment
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
  public function mergeFromDev($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    $multidev_ids = array_map(
      function($env) {
        $env_id = $env->get('id');
        return $env_id;
      },
      $site->environments->multidev()
    );
    $multidev_id = Input::env(
      array(
        'args'    => $assoc_args,
        'label'   =>
          'Multidev environment that the dev environment will be merged into',
        'choices' => $multidev_ids
      )
    );
    $environment = $site->environments->get($multidev_id);

    $workflow = $environment->mergeFromDev();
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Merge a multidev environment into dev environment
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
  public function mergeToDev($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));

    $multidev_ids = array_map(
      function($env) {
        $env_id = $env->get('id');
        return $env_id;
      },
      $site->environments->multidev()
    );
    $multidev_id = Input::env(
      array(
        'args' => $assoc_args,
        'label' => 'Multidev environment to merge into dev environment',
        'choices' => $multidev_ids
      )
    );
    $environment = $site->environments->get($multidev_id);

    $workflow = $environment->mergeToDev();
    $workflow->wait();

    $this->log()->info(
      'Merged the {env} environment into dev',
      array('env' => $environment->get('id'))
    );
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
          $this->failure(
            "Organization is either invalid or you are not a member."
          );
        }
        $workflow = $site->org_memberships->addMember($org, $role);
        $workflow->wait();
          break;
      case 'remove':
        $org = Input::orgid($assoc_args, 'org');
        if (!$this->isOrgAccessible($org)) {
          $this->failure(
            "Organization is either invalid or you are not a member."
          );
        }
        $member = $site->org_memberships->get($org);
        if ($member == null) {
          $this->failure(
            '{org} is not a member of {site}',
            array('org' => $org, 'site' => $site->get('name'))
          );
        }
        $workflow = $member->removeMember('organization', $org);
        $workflow->wait();
          break;
      case 'list':
      default:
        $orgs = $site->org_memberships->all();
        if (empty($orgs)) {
          $this->log()->warning('No organizations');
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
        $this->output()->outputRecordList($data);
          break;
    }
    if (isset($workflow)) {
      $this->workflowOutput($workflow);
    }
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
   */
  public function mount($args, $assoc_args) {
    exec('which sshfs', $stdout, $exit);
    if ($exit !== 0) {
      $this->failure('Must install sshfs first');
    }

    $destination = Utils\destinationIsValid($assoc_args['destination']);

    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = Input::env(array('args' => $assoc_args, 'site' => $site));

    exec('uname', $output, $ret);
    $darwin = '';
    if (is_array($output)
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
    $cmd  = sprintf(
      'sshfs %s -p 2222 %s@%s:./ %s',
      $darwin,
      $user,
      $host,
      $destination
    );
    exec($cmd, $stdout, $exit);
    if ($exit != 0) {
      $this->failure("Couldn't mount $destination");
    }
    $message  = 'Site mounted to {destination}.';
    $message .= ' To unmount, run: umount {destination}';
    $message .= ' (or fusermount -u {destination}).';
    $this->log()->info($message, compact('destination'));
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
  public function newRelic($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $data = $site->newRelic();
    if (!empty($data->account)) {
      $this->output()->outputRecord($data->account);
    } else {
      $this->log()->warning('New Relic is not enabled.');
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
    $this->output()->outputValue($site->get('owner'), 'Site Owner');
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
   */
  public function redis($args, $assoc_args) {
    $action = array_shift($args);
    $site   = $this->sites->get(Input::sitename($assoc_args));
    if (isset($assoc_args['env'])) {
      $env = $assoc_args['env'];
    }
    switch ($action) {
      case 'clear':
        $bindings = $site->bindings('cacheserver');
        if (empty($bindings)) {
          $this->failure('Redis cache not enabled');
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
          array_filter(
            $args,
            function($a) {
              $escaped_arg = escapeshellarg($a);
              return $escaped_arg;
            }
          );
          $command  = 'ssh -p 2222 %s.%s@appserver.%s.%s.drush.in';
          $command .= ' "redis-cli -h %s -p %s -a %s flushall"';
          $commands[$binding->environment] = vsprintf($command, $args);
        }
        foreach ($commands as $env => $command) {
          $this->log()->info('Clearing redis on {env}', array('env' => $env));
          exec($command, $stdout, $return);
          $this->log()->info($stdout[0]);
        }
          break;
    }
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
   * [--mode=<value>]
   * : set connection to sftp or git
   *
   * @subcommand set-connection-mode
   */
  public function setConnectionMode($args, $assoc_args) {
    if (!isset($assoc_args['mode'])
      || !in_array($assoc_args['mode'], array('sftp', 'git'))
    ) {
      $this->failure('You must specify the mode as either sftp or git.');
    }
    $mode = strtolower($assoc_args['mode']);
    $site = $this->sites->get(Input::sitename($assoc_args));
    // Only present dev and multidev environments; Test/Live cannot be modified
    $environments = array_diff(
      $site->environments->ids(),
      array('test', 'live')
    );

    $env = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'choices' => $environments))
    );
    if (in_array($env->get('id'), array('test', 'live'))) {
      $this->failure(
        'Connection mode cannot be set in Test or Live environments'
      );
    }
    try {
      $current_mode = $env->info('connection_mode');
      if ($current_mode == $env->info('connection_mode')) {
        $this->failure(
          'The connection mode on {site} for {env} is already set to {mode}.',
          array(
            'site' => $site->get('name'),
            'env' => $env->get('id'),
            'mode' => $mode
          ),
          -1
        );
      }
    } catch (TerminusException $e) {
      $this->log()->info(
        'Current connection info not available. Proceeding with mode change.'
      );
    }
    $workflow = $env->changeConnectionMode($mode);
    if (is_string($workflow)) {
      $this->log()->info($workflow);
    } else {
      $workflow->wait();
      $this->workflowOutput($workflow);
    }
    return true;
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
  public function setInstrument($args, $assoc_args) {
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

    if (!isset($instruments[$instrument_id])
      && !in_array($instrument_id, Input::$NULL_INPUTS)
    ) {
      $this->failure(
        'You do not have permission to attach instrument {instrument_id}',
        compact('instrument_id')
      );
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
  public function setOwner($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $workflow = $site->setOwner($assoc_args['set']);
    $workflow->wait();
    $this->workflowOutput($workflow);
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
  public function setServiceLevel($args, $assoc_args) {
    $site  = $this->sites->get(Input::sitename($assoc_args));
    $info  = $site->get('service_level');
    $level = $assoc_args['level'];
    $data  = $site->updateServiceLevel($level);
    $this->log()->info("Service level has been updated to '$level'");
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
    $action = array_shift($args);
    $site   = $this->sites->get(Input::sitename($assoc_args));
    $org    = Input::orgid($assoc_args, 'org');

    if ($site->organizationIsMember($org)) {
      switch ($action) {
        case 'add':
          $tag      = Input::string($assoc_args, 'tag', 'Enter a tag to add');
          $response = $site->addTag($tag, $org);

          $context = array(
            'tag'  => $tag,
            'site' => $site->get('name')
          );
          if ($response['status_code'] == 200) {
            $this->log()->info(
              'Tag "{tag}" has been added to {site}',
              $context
            );
          } else {
            $this->failure(
              'Tag "{tag}" could not be added to {site}',
              $context
            );
          }
            break;
        case 'remove':
          $tags = $site->getTags($org);
          if (count($tags) === 0) {
            $message  = 'This organization does not have any tags associated';
            $message .= ' with this site.';
            $this->failure($message);
          } elseif (!isset($assoc_args['tag'])
            || !in_array($assoc_args['tag'], $tags)
          ) {
            $tag = $tags[Input::menu($tags, null, 'Select a tag to delete')];
          } else {
            $tag = $assoc_args['tag'];
          }
          $response = $site->removeTag($tag, $org);

          $context = array(
            'tag'  => $tag,
            'site' => $site->get('name')
          );
          if ($response['status_code'] == 200) {
            $this->log()->info(
              'Tag "{tag}" has been removed from {site}',
              $context
            );
          } else {
            $this->failure(
              'Tag "{tag}" could not be removed from {site}',
              $context
            );
          }
            break;
        case 'list':
        default:
          $tags = $site->getTags($org);
          $this->output()->outputRecord(compact('tags'));
            break;
      }
    } else {
      $message  = '{site} is not a member of an organization,';
      $message .= ' which is necessary to associate a tag with a site.';
      $this->failure($message, array('site' => $site->get('name')));
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
    $action = 'list';
    if (!empty($args)) {
      $action = array_shift($args);
    }
    $site = $this->sites->get(Input::sitename($assoc_args));
    $data = array();
    $team = $site->user_memberships;
    switch($action) {
      case 'add-member':
        if ((boolean)$site->getFeature('change_management')) {
          $role = Input::role($assoc_args);
        } else {
          $role = 'team_member';
        }
        $workflow = $team->addMember($assoc_args['member'], $role);
        $this->workflowOutput($workflow);
          break;
      case 'remove-member':
        $user = $team->get($assoc_args['member']);
        if ($user != null) {
          $workflow = $user->removeMember($assoc_args['member']);
          $this->workflowOutput($workflow);
        } else {
          $this->failure(
            '"{member}" is not a valid member.',
            array('member' => $assoc_args['member'])
          );
        }
          break;
      case 'change-role':
        if ((boolean)$site->getFeature('change_management')) {
          $role = Input::role($assoc_args);
          $user = $team->get($assoc_args['member']);
          if ($user != null) {
            $workflow = $user->setRole($role);
            $this->workflowOutput($workflow);
          } else {
            $this->failure(
              '"{member}" is not a valid member.',
              array('member' => $assoc_args['member'])
            );
          }
        } else {
          $this->failure(
            'This site does not have the authority to conduct this operation.'
          );
        }
          break;
      case 'list':
      default:
        $user_memberships = $team->all();
        foreach ($user_memberships as $uuid => $user_membership) {
          $user   = $user_membership->get('user');
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
    if (!empty($data)) {
      $this->output()->outputRecordList($data);
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
  public function upstreamInfo($args, $assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $upstream = $site->get('upstream');
    $upstream_updates   = $site->getUpstreamUpdates();
    $upstream['status'] = 'current';
    if ($upstream_updates->behind > 0) {
      $upstream['status'] = 'outdated';
    }
    $this->output()->outputRecord($upstream);
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
   * : Attempt to automatically resolve conflicts in favor of the upstream
   *   repository.
   *
   * [--updatedb]
   * : (Drupal only) run update.php after updating,
   *
   * @subcommand upstream-updates
   */
  public function upstreamUpdates($args, $assoc_args) {
    $action = 'list';
    if (!empty($args)) {
      $action = array_shift($args);
    }
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $upstream = $site->getUpstreamUpdates();

    switch($action) {
      default:
      case 'list':
        $data = array();
        if (isset($upstream->remote_url) && isset($upstream->behind)) {
          $update_log = (array)$upstream->update_log;
          if (!isset($upstream) || empty($update_log)) {
            $this->log()->info('No updates to show');
            exit(0);
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
          $this->output()->outputRecordList($data);
        } else {
          $message  = 'There was a problem checking your upstream status.';
          $message .= ' Please try again.';
          $this->log()->warning($message);
        }
          break;
      case 'apply':
        if (!empty($upstream->update_log)) {
          $env = 'dev';
          if (isset($assoc_args['env'])) {
            $env = $assoc_args['env'];
          }
          if (in_array($env, array('test', 'live'))) {
            $this->failure(
              'Upstream updates cannot be applied to the {env} environment',
              compact('env')
            );
          }

          $updatedb       = (
            isset($assoc_args['updatedb'])
            && $assoc_args['updatedb']
          );
          $acceptupstream = (
            isset($assoc_args['accept-upstream'])
            && $assoc_args['accept-upstream']
          );
          Terminus::confirm(
            sprintf(
              'Are you sure you want to apply the upstream updates to %s-dev',
              $site->get('name'),
              $env
            )
          );
          $workflow = $site->applyUpstreamUpdates(
            $env,
            $updatedb,
            $acceptupstream
          );
          $workflow->wait();
          $this->workflowOutput($workflow);
        } else {
          $this->log()->warning(
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
    $env  = Input::env(array('args' => $assoc_args, 'site' => $site));
    $data = $site->environments->get($env)->wake();
    if (!$data['success']) {
      $this->failure(
        'Could not reach {target}',
        $data
      );
    }
    if (!$data['styx']) {
      $this->failure('Pantheon headers missing, which is not quite right.');
    }
    $context = array('target' => $data['target'], 'time' => $data['time']);
    $this->log()->info(
      'OK >> {target} responded in {time}',
      $context
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
    $env  = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );

    Terminus::confirm(
      sprintf(
        'Are you sure you want to wipe %s-%s?',
        $site->get('name'),
        $env->get('id')
      )
    );

    $workflow = $env->wipe();
    $workflow->wait();
    $this->log()->info(
      'Successfully wiped {site}-{env}',
      array(
        'site' => $site->get('name'),
        'env' => $env->get('id')
      )
    );
  }

  /**
   * Cancels an environment's regular backup schedule
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [void]
   */
  private function cancelBackupSchedule($assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $env      = $site->environments->get(
      Input::env(
        array('args' => $assoc_args, 'choices' => array('dev', 'live'))
      )
    );
    $success = $env->backups->cancelBackupSchedule();
    $this->log()->info('Cancelled backup schedule.');
  }

  /**
   * Creates a backup
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [Workflow] $workflow
   */
  private function createBackup($assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );
    $args = $assoc_args;
    unset($args['site']);
    unset($args['env']);
    $args['element'] = Input::backupElement(
      array(
        'args'    => $args,
        'choices' => array('all', 'code', 'database', 'files'),
      )
    );
    $workflow        = $env->backups->create($args);
    return $workflow;
  }

  /**
   * Retrieves a single backup or downloads it as requested
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [string] $url->url
   */
  private function getBackup($assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );
    $file = Input::optional('file', $assoc_args, false);
    if ($file) {
      $backup  = $env->backups->getBackupByFileName($file);
      $element = $backup->getElement();
    } else {
      $element = Input::backupElement(array('args' => $assoc_args));
      $latest  = (boolean)Input::optional('latest', $assoc_args, false);
      $backups = $env->backups->getFinishedBackups($element);

      if ($latest) {
        $backup = array_pop($backups);
      } else {
        $context = array(
          'site' => $site->get('name'),
          'env' => $env->get('id')
        );
        $backup  = Input::backup(
          array('backups' => $backups, 'context' => $context)
        );
      }
    }

    $url = $backup->getUrl();

    if (isset($assoc_args['to'])) {
      $target = str_replace('~', $_SERVER['HOME'], $assoc_args['to']);
      if (is_dir($target)) {
        $filename = Utils\getFilenameFromUrl($url);
        $target   = sprintf('%s/%s', $target, $filename);
      }
      $this->log()->info('Downloading ... please wait ...');
      if (Request::download($url, $target)) {
        $this->log()->info('Downloaded {target}', compact('target'));
        return $target;
      } else {
        $this->failure('Could not download file');
      }
    }
    return $url;
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

  /**
   * Lists available backups
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [array] $data Elements as follows:
   *         [string] file The backup's file name
   *         [string] size The backup file's size
   *         [string] date The datetime of the backup's creation
   */
  private function listBackups($assoc_args) {
    $site    = $this->sites->get(Input::sitename($assoc_args));
    $env     = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );
    $element = null;
    if (isset($assoc_args['element']) && ($assoc_args['element'] != 'all')) {
      $element = Input::backupElement(array('args' => $assoc_args));
    }
    $backups = $env->backups->getFinishedBackups($element);
    $latest  = (boolean)Input::optional('latest', $assoc_args, false);
    if (empty($backups)) {
      $this->log()->warning('No backups found.');
    } else {
      if ($latest) {
        array_splice($backups, 1);
      }
      $data = array();
      foreach ($backups as $id => $backup) {
        $data[] = array(
          'file'      => $backup->get('filename'),
          'size'      => $backup->getSizeInMb(),
          'date'      => $backup->getDate(),
          'initiator' => $backup->getInitiator(),
        );
      }

      return $data;
    }
  }

  /**
   * Loads a single backup
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [boolean] Always true, else the function has thrown an exception
   */
  private function loadBackup($assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $env  = $site->environments->get(
      Input::env(array('args' => $assoc_args, 'site' => $site))
    );

    $assoc_args['to']      = '/tmp';
    $assoc_args['element'] = 'database';
    if (isset($assoc_args['database'])) {
      $database = $assoc_args['database'];
    } else {
      $database = escapeshellarg(
        Terminus::prompt('Name of database to import to')
      );
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
      $this->failure(
        'MySQL does not appear to be installed on your server.'
      );
    }

    $assoc_args['env'] = $env->get('id');
    $target = $this->backup(array('get'), $assoc_args);
    $target = '/tmp/' . Utils\getFilenameFromUrl($target);

    if (!file_exists($target)) {
      $this->failure(
        'Cannot read database file {target}',
        compact('target')
      );
    }

    $this->log()->info('Unziping database');
    exec("gunzip $target", $stdout, $exit);

    // trim the gz of the target
    $target = Utils\sqlFromZip($target);
    $target = escapeshellarg($target);
    exec(
      sprintf(
        'mysql %s -u %s -p"%s" < %s',
        $database,
        $username,
        $password,
        $target
      ),
      $stdout,
      $exit
    );
    if ($exit != 0) {
      $this->failure('Could not import database');
    }

    $this->log()->info(
      '{target} successfully imported to {db}',
      array('target' => $target, 'db' => $database)
    );
    return true;
  }

  /**
   * Sets an environment's regular backup schedule
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [void]
   */
  private function setBackupSchedule($assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $env      = $site->environments->get(
      Input::env(
        array('args' => $assoc_args, 'choices' => array('dev', 'live'))
      )
    );
    $day      = Input::day(array('args' => $assoc_args));
    $schedule = $env->backups->setBackupSchedule($day);
    $this->log()->info('Backup schedule successfully set.');
  }

  /**
   * Displays an environment's regular backup schedule
   *
   * @params [array] $assoc_args Parameters and flags from the command line
   * @return [void]
   */
  private function showBackupSchedule($assoc_args) {
    $site     = $this->sites->get(Input::sitename($assoc_args));
    $env      = $site->environments->get(
      Input::env(
        array('args' => $assoc_args, 'choices' => array('dev', 'live'))
      )
    );
    $schedule = $env->backups->getBackupSchedule();
    if (is_null($schedule['daily_backup_hour'])) {
      $this->log()->info('Backups are not currently scheduled to be run.');
    } else {
      $this->output()->outputRecord($schedule);
    }
  }

}

Terminus::addCommand('site', 'SiteCommand');
