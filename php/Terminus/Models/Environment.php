<?php

namespace Terminus\Models;

use GuzzleHttp\TransferStats as TransferStats;
use Terminus\Request;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\Backups;
use Terminus\Models\Collections\Bindings;
use Terminus\Models\Collections\Commits;
use Terminus\Models\Collections\Hostnames;

class Environment extends TerminusModel {
  /**
   * @var Backups
   */
  public $backups;

  /**
   * @var Commits
   */
  public $commits;

  /**
   * @var Bindings
   */
  public $bindings;

  /**
   * @var Hostnames
   */
  public $hostnames;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes, array $options = array()) {
    parent::__construct($attributes, $options);
    $options = ['environment' => $this];
    $this->backups   = new Backups($options);
    $this->bindings  = new Bindings($options);
    $this->commits   = new Commits($options);
    $this->hostnames = new Hostnames($options);
  }

  /**
   * Changes connection mode
   *
   * @param string $value Connection mode, "git" or "sftp"
   * @return Workflow|string
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
   * Clones database from this environment to another
   *
   * @param string $to_env Environment to clone into
   * @return Workflow
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
   * @param string $to_env Environment to clone into
   * @return Workflow
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
   * @param string $commit Should be the commit message to use if committing
   *   on server changes
   * @return array Response data
   */
  public function commitChanges($commit = null) {
    ob_start();
    passthru('git config user.email');
    $git_email = ob_get_clean();
    ob_start();
    passthru('git config user.name');
    $git_user = ob_get_clean();

    $params   = array(
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
   * @return array
   */
  public function connectionInfo() {
    $info = array();

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

    // Can only Use Git on dev/multidev environments
    if (!in_array($this->get('id'), array('test', 'live'))) {
      $git_username = sprintf(
        'codeserver.dev.%s',
        $this->site->get('id')
      );
      $git_host     = sprintf(
        'codeserver.dev.%s.drush.in',
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
    }

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
        $next_binding = array_shift($cacheserver_binding);
        if (is_null($next_binding)) {
          break;
        }
        $cache_binding = $next_binding;
      } while (!is_null($cache_binding)
        && $cache_binding->get('environment') != $this->get('id')
      );

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
   * Counts the number of deployable commits
   *
   * @return int
   */
  public function countDeployableCommits() {
    $parent_environment = $this->getParentEnvironment();
    $parent_commits     = $parent_environment->commits->all();
    $number_of_commits  = 0;
    foreach ($parent_commits as $commit) {
      $labels             = $commit->get('labels');
      $number_of_commits += (integer)(
        !in_array($this->get('id'), $labels)
        && in_array($parent_environment->get('id'), $labels)
      );
    }
    return $number_of_commits;
  }

  /**
   * Creates a new environment
   *
   * @param string $env_name Name of environment to create
   * @return array Response data
   */
  public function create($env_name) {
    $path     = sprintf('environments/%s', $env_name);
    $params   = array();
    $response = $this->request->request(
      'sites',
      $this->site->get('id'),
      $path,
      'POST',
      $params
    );
    return $response['data'];
  }

  /**
   * Deploys the Test or Live environment
   *
   * @param array $params Parameters for the deploy workflow
   * @return Workflow
   */
  public function deploy($params) {
    $params   = array('environment' => $this->get('id'), 'params' => $params);
    $workflow = $this->site->workflows->create('deploy', $params);
    return $workflow;
  }

  /**
   * Gets diff from multidev environment
   *
   * @return array
   */
  public function diffstat() {
    $path = sprintf(
      'environments/%s/on-server-development/diffstat',
      $this->get('id')
    );
    $data = $this->request->request(
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
   * @return string
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
   * @return string 'git' or 'sftp'
   */
  public function getConnectionMode() {
    $path   = sprintf(
      'environments/%s/on-server-development',
      $this->get('id')
    );
    $result = $this->request->request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    $mode   = 'git';
    if ($result['data']->enabled) {
      $mode = 'sftp';
    }
    return $mode;
  }

  /**
   * Returns the environment's name
   *
   * @return string
   */
  public function getName() {
    $name = $this->get('id');
    return $name;
  }

  /**
   * Returns the parent environment
   *
   * @return Environment
   */
  public function getParentEnvironment() {
    $env_id = $this->get('id');
    if ($env_id == 'dev') {
      return null;
    }
    switch ($this->get('id')) {
      case 'dev':
          return null;
          break;
      case 'live':
        $parent_env_id = 'test';
          break;
      default:
        $parent_env_id = 'dev';
          break;
    }
    $environment = $this->site->environments->get($parent_env_id);
    return $environment;
  }

  /**
   * Decides if the environment has changes to deploy
   *
   * @return bool
   */
  public function hasDeployableCode() {
    $number_of_commits = $this->countDeployableCommits();
    return (boolean)$number_of_commits;
  }

  /**
   * Load site info
   *
   * @param string $key Set to retrieve a specific attribute as named
   * @return array $info
   * @throws TerminusException
   */
  public function info($key = null) {
    $path   = sprintf('environments/%s', $this->get('id'));
    $result = $this->request->request(
      'sites',
      $this->site->get('id'),
      $path,
      'GET'
    );
    $connection_mode = 'git';
    if (property_exists($result['data'], 'on_server_development')
      && (boolean)$result['data']->on_server_development
    ) {
      $connection_mode = 'sftp';
    }
    $php_version = $this->site->info()['php_version'];
    if (property_exists($result['data'], 'php_version')) {
      if ($result['data']->php_version == '55') {
        $php_version = '5.5';
      } elseif ($result['data']->php_version == '53') {
        $php_version = '5.3';
      }
    }
    $info = [
      'id'              => $this->get('id'),
      'connection_mode' => $connection_mode,
      'php_version'     => $php_version,
    ];

    if ($key) {
      if (isset($info[$key])) {
        return $info[$key];
      } else {
        throw new TerminusException('There is no such field.', [], 1);
      }
    } else {
      return $info;
    }
  }

  /**
   * Initializes the test/live environments on a newly created site  and clones
   * content from previous environment (e.g. test clones dev content, live
   * clones test content.)
   *
   * @return Workflow In-progress workflow
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
   * @return bool True if environment has been instantiated
   */
  public function isInitialized() {
    // One can determine whether an environment has been initialized
    // by checking if it has code commits. Uninitialized environments do not.
    $commits     = $this->commits->all();
    $has_commits = (count($commits) > 0);
    return $has_commits;
  }

  /**
   * Is this branch a multidev environment?
   *
   * @return bool True if ths environment is a multidev environment
   */
  public function isMultidev() {
    $is_multidev = !in_array($this->get('id'), array('dev', 'test', 'live'));
    return $is_multidev;
  }

  /**
   * Enable HTTP Basic Access authentication on the web environment
   *
   * @param array $options Parameters to override defaults
   * @return Workflow
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
   * @return string
   */
  public function lockinfo() {
    $lock = $this->get('lock');
    return $lock;
  }

  /**
   * Merge code from the Dev Environment into this Multidev Environment
   *
   * @param array $options Parameters to override defaults
   * @return Workflow
   * @throws TerminusException
   */
  public function mergeFromDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new TerminusException(
        'The {env} environment is not a multidev environment',
        ['env' => $this->get('id')],
        1
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
   * @param array $options Parameters to override defaults
   * @return Workflow
   * @throws TerminusException
   */
  public function mergeToDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new TerminusException(
        'The {env} environment is not a multidev environment',
        ['env' => $this->get('id')],
        1
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
   * Add/Replace an HTTPS Certificate on the Environment
   *
   * @param array $options Certificate data`
   *
   * @return $workflow
   */
  public function setHttpsCertificate($options = array()) {
    $params = array(
      'cert' => $options['certificate'],
      'key' => $options['private_key']
    );

    if (isset($options['intermediate_certificate'])) {
      $params['intermediary'] = $options['intermediate_certificate'];
    }

    $response = $this->request->simpleRequest(
      sprintf(
        'sites/%s/environments/%s/add-ssl-cert',
        $this->site->get('id'),
        $this->get('id')
      ),
      array('method' => 'post', 'form_params' => $params)
    );

    // The response to the PUT is actually a workflow
    $workflow_data = $response['data'];
    $workflow = new Workflow($workflow_data);
    return $workflow;
  }

  /**
   * Sets the PHP version number of this environment
   *
   * @param string $version_number The version number to set this environment to
   *  use
   * @return void
   */
  public function setPhpVersion($version_number) {
    $options = [
      'environment' => $this->get('id'),
      'params'      => [
        'key'   => 'php_version',
        'value' => $version_number,
      ],
    ];
    $workflow = $this->site->workflows->create(
      'update_environment_setting',
      $options
    );
    return $workflow;
  }

  /**
   * Disable HTTP Basic Access authentication on the web environment
   *
   * @return Workflow
   */
  public function unlock() {
    $params   = array('environment' => $this->get('id'));
    $workflow = $this->site->workflows->create('unlock_environment', $params);
    return $workflow;
  }

  /**
   * Unsets the PHP version of this environment so it will use the site default
   *
   * @return void
   */
  public function unsetPhpVersion() {
    $options = [
      'environment' => $this->get('id'),
      'params'      => ['key' => 'php_version',],
    ];
    $workflow = $this->site->workflows->create(
      'delete_environment_setting',
      $options
    );
    return $workflow;
  }

  /**
   * "Wake" a site
   *
   * @return array
   */
  public function wake() {
    $on_stats = function (TransferStats $stats) {
      $this->transfertime = $stats->getTransferTime();
    };
    $hostnames   = $this->hostnames->ids();
    $target      = array_pop($hostnames);
    $healthc     = "http://$target/pantheon_healthcheck";
    $response    = $this->request->simpleRequest($healthc, compact('on_stats'));
    $return_data = array(
      'success'  => ($response['status_code'] === 200),
      'time'     => $this->transfertime,
      'styx'     => $response['headers']['X-Pantheon-Styx-Hostname'],
      'response' => $response,
      'target'   => $target,
    );

    return $return_data;
  }

  /**
   * Deletes all content (files and database) from the Environment
   *
   * @return Workflow
   */
  public function wipe() {
    $params   = array('environment' => $this->get('id'));
    $workflow = $this->site->workflows->create('wipe', $params);
    return $workflow;
  }

  /**
   * Start a work flow
   *
   * @param Workflow $workflow String work flow "slot"
   * @return array
   */
  public function workflow(Workflow $workflow) {
    $path        = sprintf("environments/%s/workflows", $this->get('id'));
    $form_params = array(
      'type'        => $workflow,
      'environment' => $this->get('id'),
    );
    $response    = $this->request->request(
      'sites',
      $this->site->get('id'),
      $path,
      'POST',
      compact('form_params')
    );

    return $response['data'];
  }

}
