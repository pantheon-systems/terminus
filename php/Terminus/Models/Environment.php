<?php

namespace Terminus\Models;

use GuzzleHttp\TransferStats as TransferStats;
use Terminus\Exceptions\TerminusException;
use Terminus\Collections\Backups;
use Terminus\Collections\Bindings;
use Terminus\Collections\Commits;
use Terminus\Collections\Hostnames;
use Terminus\Collections\Workflows;

class Environment extends TerminusModel {
  /**
   * @var Backups
   */
  public $backups;
  /**
   * @var Bindings
   */
  public $bindings;
  /**
   * @var Commits
   */
  public $commits;
  /**
   * @var Hostnames
   */
  public $hostnames;
  /**
   * @var Site
   */
  public $site;
  /**
   * @var Workflows
   */
  public $workflows;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes, array $options = []) {
    parent::__construct($attributes, $options);
    $this->site = $options['collection']->site;
    $options = ['environment' => $this,];
    $this->backups   = new Backups($options);
    $this->bindings  = new Bindings($options);
    $this->commits   = new Commits($options);
    $this->hostnames = new Hostnames($options);
    $this->workflows = new Workflows($options);
  }

  /**
   * Changes connection mode
   *
   * @param string $value Connection mode, "git" or "sftp"
   * @return Workflow|string
   */
  public function changeConnectionMode($value) {
    $current_mode = $this->info('connection_mode');
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

    $workflow = $this->workflows->create($workflow_name);
    return $workflow;
  }

  /**
   * Clears an environment's cache
   *
   * @return Workflow
   */
  public function clearCache() {
    $workflow = $this->workflows->create(
      'clear_cache',
      ['params' => ['framework_cache' => true,],]
    );
    return $workflow;
  }

  /**
   * Clones database from this environment to another
   *
   * @param string $from_env Name of the environment to clone
   * @return Workflow
   */
  public function cloneDatabase($from_env) {
    $params = ['from_environment' => $from_env,];
    $workflow = $this->workflows->create('clone_database', compact('params'));
    return $workflow;
  }

  /**
   * Clones files from this environment to another
   *
   * @param string $from_env Name of the environment to clone
   * @return Workflow
   */
  public function cloneFiles($from_env) {
    $params = ['from_environment' => $from_env,];
    $workflow = $this->workflows->create('clone_files', compact('params'));
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

    $params   = [
      'message'         => $commit,
      'committer_name'  => $git_user,
      'committer_email' => $git_email,
    ];
    $workflow = $this->workflows->create(
      'commit_and_push_on_server_changes',
      compact('params')
    );
    return $workflow;
  }

  /**
   * Gives connection info for this environment
   *
   * @return array
   */
  public function connectionInfo() {
    $info = [];

    $sftp_username = sprintf(
      '%s.%s',
      $this->id,
      $this->site->id
    );
    $sftp_password = 'Use your account password';
    $sftp_host     = sprintf(
      'appserver.%s.%s.drush.in',
      $this->id,
      $this->site->id
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
    $sftp_params   = [
      'sftp_username' => $sftp_username,
      'sftp_host'     => $sftp_host,
      'sftp_password' => $sftp_password,
      'sftp_url'      => $sftp_url,
      'sftp_command'  => $sftp_command,
    ];
    $info = array_merge($info, $sftp_params);

    // Can only Use Git on dev/multidev environments
    if (!in_array($this->id, ['test', 'live',])) {
      $git_username = sprintf(
        'codeserver.dev.%s',
        $this->site->id
      );
      $git_host     = sprintf(
        'codeserver.dev.%s.drush.in',
        $this->site->id
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
      $git_params   = [
        'git_username' => $git_username,
        'git_host'     => $git_host,
        'git_port'     => $git_port,
        'git_url'      => $git_url,
        'git_command'  => $git_command,
      ];
      $info = array_merge($info, $git_params);
    }

    $dbserver_binding = (array)$this->bindings->getByType('dbserver');
    if (!empty($dbserver_binding)) {
      do {
        $db_binding = array_shift($dbserver_binding);
      } while ($db_binding->get('environment') != $this->id);

      $mysql_username = 'pantheon';
      $mysql_password = $db_binding->get('password');
      $mysql_host     = sprintf(
        'dbserver.%s.%s.drush.in',
        $this->id,
        $this->site->id
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
      $mysql_params   = [
        'mysql_host'     => $mysql_host,
        'mysql_username' => $mysql_username,
        'mysql_password' => $mysql_password,
        'mysql_port'     => $mysql_port,
        'mysql_database' => $mysql_database,
        'mysql_url'      => $mysql_url,
        'mysql_command'  => $mysql_command,
      ];

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
        && $cache_binding->get('environment') != $this->id
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
      $redis_params   = [
        'redis_password' => $redis_password,
        'redis_host'     => $redis_host,
        'redis_port'     => $redis_port,
        'redis_url'      => $redis_url,
        'redis_command'  => $redis_command,
      ];

      $info = array_merge($info, $redis_params);
    }

    return $info;
  }

  /**
   * Converges all bindings on a site
   *
   * @return array
   */
  public function convergeBindings() {
    $workflow = $this->workflows->create('converge_environment');
    return $workflow;
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
        !in_array($this->id, $labels)
        && in_array($parent_environment->id, $labels)
      );
    }
    return $number_of_commits;
  }

  /**
   * Delete a multidev environment
   *
   * @param array $arg_options Elements as follow:
   *   bool delete_branch True to delete branch
   * @return Workflow
   */
  public function delete(array $arg_options = []) {
    $default_options = ['delete_branch' => false,];
    $options         = array_merge($default_options, $arg_options);
    $params          = array_merge(
      ['environment_id' => $this->id,],
      $options
    );
    $workflow = $this->site->workflows->create(
      'delete_cloud_development_environment',
      compact('params')
    );
    return $workflow;
  }

  /**
   * Deploys the Test or Live environment
   *
   * @param array $params Parameters for the deploy workflow
   * @return Workflow
   */
  public function deploy($params) {
    $workflow = $this->workflows->create('deploy', compact('params'));
    return $workflow;
  }

  /**
   * Gets diff from multidev environment
   *
   * @return array
   */
  public function diffstat() {
    $path    = sprintf(
      'sites/%s/environments/%s/on-server-development/diffstat',
      $this->site->id,
      $this->id
    );
    $options = ['method' => 'get',];
    $data    = $this->request->request($path, $options);
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
      $this->id,
      $this->site->get('name'),
      $this->get('dns_zone')
    );
    return $host;
  }

  /**
   * Gets the Drush version of this environment
   *
   * @return int
   */
  public function getDrushVersion() {
    $version = (integer)$this->getSettings('drush_version');
    return $version;
  }

  /**
   * Returns the environment's name
   *
   * @return string
   */
  public function getName() {
    return $this->id;
  }

  /**
   * Returns the parent environment
   *
   * @return Environment
   */
  public function getParentEnvironment() {
    switch ($this->id) {
      case 'dev':
          return null;
      case 'live':
        $parent_env_id = 'test';
          break;
      case 'test':
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
   * Imports a database archive
   *
   * @param string $url URL to import data from
   * @return Workflow
   */
  public function importDatabase($url) {
    $workflow = $this->workflows->create(
      'import_database',
      ['params' => compact('url'),]
    );
    return $workflow;
  }

  /**
   * Imports a site archive onto Pantheon
   *
   * @param string $url URL of the archive to import
   * @return Workflow
   */
  public function import($url) {
    $workflow = $this->workflows->create(
      'do_migration',
      ['params' => compact('url'),]
    );
    return $workflow;
  }

  /**
   * Imports a file archive
   *
   * @param string $url URL to import data from
   * @return Workflow
   */
  public function importFiles($url) {
    $workflow = $this->workflows->create(
      'import_files',
      ['params' => compact('url'),]
    );
    return $workflow;
  }

  /**
   * Load site info
   *
   * @param string $key Set to retrieve a specific attribute as named
   * @return array $info
   * @throws TerminusException
   */
  public function info($key = null) {
    $path    = sprintf(
      'sites/%s/environments/%s',
      $this->site->id,
      $this->id
    );
    $options = ['method' => 'get',];
    $result  = $this->request->request($path, $options);
    $connection_mode = 'git';
    if (property_exists($result['data'], 'on_server_development')
      && (boolean)$result['data']->on_server_development
    ) {
      $connection_mode = 'sftp';
    }
    $php_version = $this->site->info()['php_version'];
    if (property_exists($result['data'], 'php_version')) {
      $php_version = substr($result['data']->php_version, 0, 1)
        . '.' . substr($result['data']->php_version, 1, 1);
    }
    $info = [
      'id'              => $this->id,
      'connection_mode' => $connection_mode,
      'php_version'     => $php_version,
    ];

    if ($key) {
      if (isset($info[$key])) {
        return $info[$key];
      } else {
        throw new TerminusException(
          'There is no field {field}.',
          ['field' => $key,],
          1
        );
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
    if ($this->id == 'test') {
      $from_env_id = 'dev';
    } elseif ($this->id == 'live') {
      $from_env_id = 'test';
    }

    $params = [
      'annotation'     => sprintf(
        'Create the %s environment',
        $this->id
      ),
      'clone_database' => ['from_environment' => $from_env_id,],
      'clone_files'    => ['from_environment' => $from_env_id,],
    ];
    $workflow = $this->workflows->create(
      'create_environment',
      compact('params')
    );
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
    $is_multidev = !in_array($this->id, ['dev', 'test', 'live']);
    return $is_multidev;
  }

  /**
   * Enable HTTP Basic Access authentication on the web environment
   *
   * @param array $params Elements as follow:
   *        string username
   *        string password
   * @return Workflow
   */
  public function lock($params) {
    $workflow = $this->workflows->create(
      'lock_environment',
      compact('params')
    );
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
  public function mergeFromDev($options = []) {
    if (!$this->isMultidev()) {
      throw new TerminusException(
        'The {env} environment is not a multidev environment',
        ['env' => $this->id],
        1
      );
    }
    $default_params = ['updatedb' => false,];

    $params   = array_merge($default_params, $options);
    $workflow = $this->workflows->create(
      'merge_dev_into_cloud_development_environment',
      compact('params')
    );

    return $workflow;
  }

  /**
   * Merge code from a multidev environment into the dev environment
   *
   * @param array $options Parameters to override defaults
   *        string  from_environment Name of the multidev environment to merge
   *        boolean updatedb         True to update DB with merge
   * @return Workflow
   * @throws TerminusException
   */
  public function mergeToDev($options = []) {
    if ($this->id != 'dev') {
      throw new TerminusException(
        'Environment::mergeToDev() may only be run on the dev environment.',
        [],
        1
      );
    }

    $default_params = ['updatedb' => false, 'from_environment' => null,];
    $params = array_merge($default_params, $options);

    $workflow = $this->workflows->create(
      'merge_cloud_development_environment_into_dev',
      compact('params')
    );

    return $workflow;
  }

  /**
   * Sets the Drush version to the indicated version number
   *
   * @param string $version_number Version of Drush to use
   * @return Workflow
   */
  public function setDrushVersion($version_number) {
    $this->updateSetting(['drush_version' => $version_number,]);
    $workflow = $this->convergeBindings();
    return $workflow;
  }

  /**
   * Add/replace an HTTPS certificate on the environment
   *
   * @param array $certificate Certificate data elements as follow
   *  string cert         Certificate
   *  string key          RSA private key
   *  string intermediary CA intermediate certificate(s)
   *
   * @return $workflow
   */
  public function setHttpsCertificate($certificate = []) {
    // Weed out nulls
    $params = array_filter(
      $certificate,
      function ($param) {
        $is_not_null = !is_null($param);
        return $is_not_null;
      }
    );

    $response = $this->request->request(
      sprintf(
        'sites/%s/environments/%s/add-ssl-cert',
        $this->site->id,
        $this->id
      ),
      ['method' => 'post', 'form_params' => $params,]
    );

    // The response to the PUT is actually a workflow
    $workflow_data = $response['data'];
    $workflow = new Workflow($workflow_data, ['environment' => $this,]);
    return $workflow;
  }

  /**
   * Disable HTTP Basic Access authentication on the web environment
   *
   * @return Workflow
   */
  public function unlock() {
    $workflow = $this->workflows->create('unlock_environment');
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
    $response    = $this->request->request($healthc, compact('on_stats'));
    $return_data = [
      'success'  => ($response['status_code'] === 200),
      'time'     => $this->transfertime,
      'styx'     => $response['headers']['X-Pantheon-Styx-Hostname'],
      'response' => $response,
      'target'   => $target,
    ];

    return $return_data;
  }

  /**
   * Deletes all content (files and database) from the Environment
   *
   * @return Workflow
   */
  public function wipe() {
    $workflow = $this->workflows->create('wipe');
    return $workflow;
  }

  /**
   * Retrieves the value of an environmental setting
   *
   * @param string $setting Name of the setting to retrieve
   * @return mixed
   */
  private function getSettings($setting = null) {
    $path   = sprintf(
      'sites/%s/environments/%s/settings',
      $this->site->id,
      $this->id
    );
    $response = (array)$this->request->request($path, ['method' => 'get',]);
    if (isset($response['data']->$setting)) {
      return $response['data']->$setting;
    }
    return (array)$response['data'];

  }

  /**
   * Changes the environment's settings
   *
   * @param array $settings Key/value pairs to set in the environment settings
   * @return bool
   */
  private function updateSetting(array $settings = []) {
    $path   = sprintf(
      'sites/%s/environments/%s/settings',
      $this->site->id,
      $this->id
    );
    $params = ['form_params' => $settings, 'method' => 'put',];
    $response = $this->request->request($path, $params);
    return (boolean)$response['data'];
  }

}
