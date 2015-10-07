<?php

namespace Terminus\Models;

use Terminus\Request;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\Bindings;

class Environment extends TerminusModel {
  private $backups;
  private $bindings;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes Attributes of this model
   * @param [array]    $options    Options to set as $this->key
   * @return [TerminusModel] $this
   */
  public function __construct($attributes, $options = array()) {
    parent::__construct($attributes, $options);
    $this->bindings = new Bindings(array('environment' => $this));
  }

  /**
   * Add hostname to environment
   *
   * @param [string] $hostname Hostname to add to environment
   * @return [array] $response['data']
   */
  public function addHostname($hostname) {
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      sprintf(
        'environments/%s/hostnames/%s',
        $this->get('id'),
        rawurlencode($hostname)
      ),
      'PUT'
    );
    return $response['data'];
  }

  /**
   * Lists all backups
   *
   * @param [string] $element e.g. code, file, db
   * @return [array] $backups
   */
  public function backups($element = null) {
    if ($this->backups == null) {
      $path     = sprintf("environments/%s/backups/catalog", $this->get('id'));
      $response = \TerminusCommand::request(
        'sites',
        $this->site->get('id'),
        $path,
        'GET'
      );

      $this->backups = $response['data'];
    }
    $backups = (array)$this->backups;
    ksort($backups);
    if ($element) {
      $element = $this->elementAsDatabase($element);
      foreach ($this->backups as $id => $backup) {
        if (!isset($backup->filename)) {
          unset($backups[$id]);
          continue;
        }
        if (!preg_match("#.*$element\.\w+\.gz$#", $backup->filename)) {
          unset($backups[$id]);
          continue;
        }
      }
    }

    return $backups;
  }

  /**
   * Gets the URL of a backup
   *
   * @param [string] $bucket  Backup folder
   * @param [string] $element e.g. files, code, database
   * @return [array] $response['data']
   */
  public function backupUrl($bucket, $element) {
    $element  = $this->elementAsDatabase($element);
    $path     = sprintf(
      'environments/%s/backups/catalog/%s/%s/s3token',
      $this->get('id'),
      $bucket,
      $element
    );
    $data     = array('method' => 'GET');
    $options  = array(
      'body'    => json_encode($data),
      'headers' => array('Content-type' => 'application/json')
    );
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'POST',
      $options
    );
    return $response['data'];
  }

  /**
   * Changes connection mode
   *
   * @param [string] $value Connection mode, "git" or "sftp"
   * @return [Workflow] $workflow
   */
  public function changeConnectionMode($value) {
    $current_mode = $this->getConnectionMode();
    if ($value == $current_mode) {
      $reply = "The connection mode is already set to $value.";
      return $reply;
    }
    switch ($value) {
      case 'git':
        $workflow_name = 'enable_git_mode';
        break;
      case 'sftp':
        $workflow_name = 'enable_on_server_development';
        break;
    }

    $params   = array('environment' => $this->get('id'));
    $workflow = $this->site->workflows->create($workflow_name, $params);
    return $workflow;
  }

  /**
   * Clones files from this environment to another
   *
   * @param [string] $to_env Environment to clone into
   * @return [Workflow] $workflow
   */
  public function cloneDatabase($to_env) {
    $params   = array(
      'environment' => $to_env,
      'params'      => array('from_environment' => $this->getName())
    );
    $workflow = $this->site->workflows->create('clone_database', $params);
    return $workflow;
  }

  /**
   * Clones files from this environment to another
   *
   * @param [string] $to_env Environment to clone into
   * @return [Workflow] $workflow
   */
  public function cloneFiles($to_env) {
    $params   = array(
      'environment' => $to_env,
      'params'      => array('from_environment' => $this->getName())
    );
    $workflow = $this->site->workflows->create('clone_files', $params);
    return $workflow;
  }

  /**
   * Commits changes to code
   *
   * @param [string] $commit Should be the commit message to use if committing
   *   on server changes
   * @return [array] $data['data']
   */
  public function commitChanges($commit = null) {
    ob_start();
    passthru('git config user.email');
    $git_email = ob_get_clean();
    ob_start();
    passthru('git config user.name');
    $git_user = ob_get_clean();

    $params = array(
      'environment' => $this->get('id'),
      'params'      => array(
        'message'         => $commit,
        'committer_name'  => $git_user,
        'committer_email' => $git_email,
      ),
    );
    $workflow = $this->site->workflows->create(
      'commit_and_push_on_server_changes',
      $params
    );
    return $workflow;
  }

  /**
   * Gives connection info for this environment
   *
   * @return [array] $info
   */
  public function connectionInfo() {
    $info = array();

    // Can only SFTP into dev/multidev environments
    if (!in_array($this->get('id'), array('test', 'live'))) {
      $sftp_username = sprintf(
        '%s.%s',
        $this->get('id'),
        $this->site->get('id')
      );
      $sftp_password = 'Use your account password';
      $sftp_host     = sprintf(
        'appserver.%s.%s.drush.in',
        $this->get('id'),
        $this->site->get('id')
      );
      $sftp_port     = 2222;
      $sftp_url      = sprintf(
        'sftp://%s@%s:%s',
        $sftp_username,
        $sftp_host,
        $sftp_port
      );
      $sftp_command  = sprintf(
        'sftp -o Port=%s %s@%s',
        $sftp_port,
        $sftp_username,
        $sftp_host
      );
      $sftp_params   = array(
        'sftp_username' => $sftp_username,
        'sftp_host'     => $sftp_host,
        'sftp_password' => $sftp_password,
        'sftp_url'      => $sftp_url,
        'sftp_command'  => $sftp_command
      );

      $info = array_merge($info, $sftp_params);
    }

    $git_username = sprintf(
      'codeserver.%s.%s',
      $this->get('id'),
      $this->site->get('id')
    );
    $git_host     = sprintf(
      'codeserver.%s.%s.drush.in',
      $this->get('id'),
      $this->site->get('id')
    );
    $git_port     = 2222;
    $git_url      = sprintf(
      'ssh://%s@%s:%s/~/repository.git',
      $git_username,
      $git_host,
      $git_port
    );
    $git_command  = sprintf(
      'git clone %s %s',
      $git_url,
      $this->site->get('name')
    );
    $git_params   = array(
      'git_username' => $git_username,
      'git_host'     => $git_host,
      'git_port'     => $git_port,
      'git_url'      => $git_url,
      'git_command'  => $git_command
    );

    $info = array_merge($info, $git_params);

    $dbserver_binding = (array)$this->bindings->getByType('dbserver');
    if (!empty($dbserver_binding)) {
      do {
        $db_binding = array_shift($dbserver_binding);
      } while ($db_binding->get('environment') != $this->get('id'));

      $mysql_username = 'pantheon';
      $mysql_password = $db_binding->get('password');
      $mysql_host     = sprintf(
        'dbserver.%s.%s.drush.in',
        $this->get('id'),
        $this->site->get('id')
      );
      $mysql_port     = $db_binding->get('port');
      $mysql_database = 'pantheon';
      $mysql_url      = sprintf(
        'mysql://%s:%s@%s:%s/%s',
        $mysql_username,
        $mysql_password,
        $mysql_host,
        $mysql_port,
        $mysql_database
      );
      $mysql_command  = sprintf(
        'mysql -u %s -p%s -h %s -P %s %s',
        $mysql_username,
        $mysql_password,
        $mysql_host,
        $mysql_port,
        $mysql_database
      );
      $mysql_params   = array(
        'mysql_host'     => $mysql_host,
        'mysql_username' => $mysql_username,
        'mysql_password' => $mysql_password,
        'mysql_port'     => $mysql_port,
        'mysql_database' => $mysql_database,
        'mysql_url'      => $mysql_url,
        'mysql_command'  => $mysql_command
      );

      $info = array_merge($info, $mysql_params);
    }

    $cacheserver_binding = (array)$this->bindings->getByType('cacheserver');
    if (!empty($cacheserver_binding)) {
      do {
        $cache_binding = array_shift($cacheserver_binding);
      } while ($cache_binding->get('environment') != $this->get('id'));

      $redis_password = $cache_binding->get('password');
      $redis_host     = $cache_binding->get('host');
      $redis_port     = $cache_binding->get('port');
      $redis_url      = sprintf(
        'redis://pantheon:%s@%s:%s',
        $redis_password,
        $redis_host,
        $redis_port
      );
      $redis_command  = sprintf(
        'redis-cli -h %s -p %s -a %s',
        $redis_host,
        $redis_port,
        $redis_password
      );
      $redis_params   = array(
        'redis_password' => $redis_password,
        'redis_host'     => $redis_host,
        'redis_port'     => $redis_port,
        'redis_url'      => $redis_url,
        'redis_command'  => $redis_command
      );

      $info = array_merge($info, $redis_params);
    }

    return $info;
  }

  /**
   * Creates a new environment
   *
   * @param [string] $env_name Name of environment to create
   * @return [array] $response['data']
   */
  public function create($env_name) {
    $path     = sprintf('environments/%s', $env_name);
    $params   = array(
      'headers' => array('Content-type' => 'application/json')
    );
    $response = \TerminusCommand::request(
      'sites',
      $site_id,
      $path,
      'POST',
      $params
    );
    return $response['data'];
  }

  /**
   * Create a backup
   *
   * @param [array] $args Array of args to dictate backup choices
   *   [string]  type     Sort of operation to conduct (e.g. backup)
   *   [integer] keep-for Days to keep the backup for
   *   [string]  element  Which aspect of the arg to back up
   * @return [Workflow] $workflow
   */
  public function createBackup($args) {
    $type = 'backup';
    if (array_key_exists('type', $args)) {
      $type = $args['type'];
    }

    $ttl = 86400 * 365;
    if (array_key_exists('keep-for', $args)) {
      $ttl = 86400 * (int)$args['keep-for'];
    }

    switch ($args['element']) {
      case 'db':
        $args['database'] = true;
          break;
      case 'code':
        $args['code'] = true;
          break;
      case 'files':
        $args['files'] = true;
          break;
      case 'all':
        $args['files']    = true;
        $args['code']     = true;
        $args['database'] = true;
          break;
    }

    $params = array(
      'entry_type' => $type,
      'code'       => isset($args['code']),
      'database'   => isset($args['database']),
      'files'      => isset($args['files']),
      'ttl'        => $ttl
    );

    $options  = array('environment' => $this->get('id'), 'params' => $params);
    $workflow = $this->site->workflows->create('do_export', $options);
    return $workflow;
  }

  /**
   * Delete hostname from environment
   *
   * @param [string] $hostname Hostname to remove from environment
   * @return [array] $response['data']
   */
  public function deleteHostname($hostname) {
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      sprintf(
        'environments/%s/hostnames/%s',
        $this->get('id'),
        rawurlencode($hostname)
      ),
      'delete'
    );
    return $response['data'];

    $options  = array('environment' => $this->get('id'), 'params' => $params);
    $workflow = $this->site->workflows->create('do_export', $options);
    $workflow->wait();

    return $workflow;
  }

  /**
   * Deploys the Test or Live environment
   *
   * @param [array] $params Parameters for the deploy workflow
   * @return [Workflow] workflow response
   */
  public function deploy($params) {
    $params   = array('environment' => $this->get('id'), 'params' => $params);
    $workflow = $this->site->workflows->create('deploy', $params);
    return $workflow;
  }

  /**
   * Gets diff from multidev environment
   *
   * @return [array] $data['data']
   */
  public function diffstat() {
    $path = sprintf(
      'environments/%s/on-server-development/diffstat',
      $this->get('id')
    );
    $data = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    return $data['data'];
  }

  /**
   * Generate environment URL
   *
   * @return [string] $host
   */
  public function domain() {
    $host = sprintf(
      '%s-%s.%s',
      $this->get('id'),
      $this->site->get('name'),
      $this->get('dns_zone')
    );
    return $host;
  }

  /**
   * Returns the connection mode of this environment
   *
   * @return [string] $connection_mode
   */
  public function getConnectionMode() {
    $path = sprintf('environments/%s/on-server-development', $this->get('id'));
    $result = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    $mode = 'git';
    if ($result['data']->enabled) {
      $mode = 'sftp';
    }
    return $mode;
  }

  /**
   * List hotnames for environment
   *
   * @return [array] $response['data']
   */
  public function getHostnames() {
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      'environments/' . $this->get('id') . '/hostnames',
      'GET'
    );
    return $response['data'];
  }

  /**
   * Returns the environment's name
   *
   * @return [string] $name
   */
  public function getName() {
    $name = $this->get('id');
    return $name;
  }

  /**
   * Initializes the test/live environments on a newly created site  and clones
   * content from previous environment (e.g. test clones dev content, live
   * clones test content.)
   *
   * @return [Workflow] $workflow In-progress workflow
   */
  public function initializeBindings() {
    if ($this->get('id') == 'test') {
      $from_env_id = 'dev';
    } elseif ($this->get('id') == 'live') {
      $from_env_id = 'test';
    }

    $params   = array(
      'environment' => $this->get('id'),
      'params'      => array(
        'annotation'     => sprintf(
          'Create the %s environment',
          $this->get('id')
        ),
        'clone_database' => array('from_environment' => $from_env_id),
        'clone_files'    => array('from_environment' => $from_env_id)
      )
    );
    $workflow = $this->site->workflows->create('create_environment', $params);
    return $workflow;
  }

  /**
   * Have the environment's bindings have been initialized?
   *
   * @return [boolean] $has_commits True if environment has been instantiated
   */
  public function isInitialized() {
    // One can determine whether an environment has been initialized
    // by checking if it has code commits. Unitialized environments do not.
    $commits     = $this->log();
    $has_commits = (count($commits) > 0);
    return $has_commits;
  }

  /**
   * Is this branch a multidev environment?
   *
   * @return [boolean] True if ths environment is a multidev environment
   */
  public function isMultidev() {
    $is_multidev = !in_array($this->get('id'), array('dev', 'test', 'live'));
    return $is_multidev;
  }

  /**
   * Enable HTTP Basic Access authentication on the web environment
   *
   * @param [array] $options Parameters to override defaults
   * @return [Workflow] $workflow;
   */
  public function lock($options = array()) {
    $username = $options['username'];
    $password = $options['password'];

    $params   = array(
      'environment' => $this->get('id'),
      'params' => array(
        'username' => $username,
        'password' => $password
      )
    );
    $workflow = $this->site->workflows->create('lock_environment', $params);
    return $workflow;
  }

  /**
   * Get Info on an environment lock
   *
   * @return [string] $lock
   */
  public function lockinfo() {
    $lock = $this->get('lock');
    return $lock;
  }

  /**
   * Get the code log (commits)
   *
   * @return [array] $response['data']
   */
  public function log() {
    $path     = sprintf('environments/%s/code-log', $this->get('id'));
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    return $response['data'];
  }

  /**
   * Merge code from the Dev Environment into this Multidev Environment
   *
   * @param [array] $options Parameters to override defaults
   * @return [Workflow] $workflow
   */
  public function mergeFromDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new Exception(
        sprintf(
          'The %s environment is not a multidev environment',
          $this->get('id')
        )
      );
    }
    $default_params = array('updatedb' => false);

    $params   = array_merge($default_params, $options);
    $settings = array('environment' => $this->get('id'), 'params' => $params);
    $workflow = $this->site->workflows->create(
      'merge_dev_into_cloud_development_environment',
      $settings
    );

    return $workflow;
  }

  /**
   * Merge code from this Multidev Environment into the Dev Environment
   *
   * @param [array] $options Parameters to override defaults
   * @return [Workflow] $workflow
   */
  public function mergeToDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new Exception(
        sprintf(
          'The %s environment is not a multidev environment',
          $this->get('id')
        )
      );
    }

    $default_params = array(
      'updatedb' => false
    );
    $params         = array_merge($default_params, $options);

    // This function is a little odd because we invoke it on a
    // multidev environment, but it applies a workflow to the 'dev' environment
    $params['from_environment'] = $this->get('id');
    $settings = array('environment' => 'dev', 'params' => $params);
    $workflow = $this->site->workflows->create(
      'merge_cloud_development_environment_into_dev',
      $settings
    );

    return $workflow;
  }

  /**
   * Disable HTTP Basic Access authentication on the web environment
   *
   * @return [Workflow] $workflow
   */
  public function unlock() {
    $params   = array('environment' => $this->get('id'));
    $workflow = $this->site->workflows->create('unlock_environment', $params);
    return $workflow;
  }

  /**
   * "Wake" a site
   *
   * @return [array] $return_data
   */
  public function wake() {
    $hostnames   = $this->getHostnames();
    $target      = key($hostnames);
    $response    = Request::send("http://$target/pantheon_healthcheck", 'GET');
    $return_data = array(
      'success'  => $response->isSuccessful(),
      'time'     => $response->getInfo('total_time'),
      'styx'     => $response->getHeader('X-Pantheon-Styx-Hostname'),
      'response' => $response,
      'target'   => $target,
    );
    return $return_data;
  }

  /**
   * Deletes all content (files and database) from the Environment
   *
   * @return [Workflow] $workflow
   */
  public function wipe() {
    $params   = array('environment' => $this->get('id'));
    $workflow = $this->site->workflows->create('wipe', $params);
    return $workflow;
  }

  /**
   * Start a work flow
   *
   * @param [Workflow] $workflow String work flow "slot"
   * @return [array] $response['data']
   */
  public function workflow($workflow) {
    $path     = sprintf("environments/%s/workflows", $this->get('id'));
    $data     = array(
      'type'        => $workflow,
      'environment' => $this->get('id'),
    );
    $options  = array(
      'body'    => json_encode($data),
      'headers' => array('Content-type' => 'application/json')
    );
    $response = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'POST',
      $options
    );

    return $response['data'];
  }

  /**
   * Returns its argument unless that argument is "db", then returns "database"
   *
   * @param [string] $element Represents the request element
   * @return [string] $element or "database"
   */
  private function elementAsDatabase($element) {
    if ($element == 'db') {
      return 'database';
    }
    return $element;
  }

  /**
   * Load site info
   *
   * @param [string] $key Set to retrieve a specific attribute as named
   * @return [array] $info
   */
  public function info($key = null) {
    $path = sprintf('environments/%s', $this->get('id'));
    $result = \TerminusCommand::request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    $connection_mode = null;
    if (isset($result['data']->on_server_development)) {
      $connection_mode = 'git';
      if ((boolean)$result['data']->on_server_development) {
        $connection_mode = 'sftp';
      }
    }
    $info = array(
      'id'              => $this->get('id'),
      'connection_mode' => $connection_mode,
      'php_version'     => $this->site->info('php_version'),
    );

    if ($key) {
      if (isset($info[$key])) {
        return $info[$key];
      } else {
        throw new TerminusException('There is no such field.', array(), -1);
      }
    } else {
      return $info;
    }
  }

}
