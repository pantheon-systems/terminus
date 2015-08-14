<?php
namespace Terminus;
use \ReflectionClass;
use \Terminus\Request;
use \Terminus\Collections\Bindings;

class Environment {
  public $id;
  public $attributes;
  public $bindings;

  public $name = 'dev';
  public $site = false;
  public $diffstat;
  public $dns_zone;
  public $environment_created;
  public $lock;
  public $on_server_development;
  public $randseed;
  public $styx_cluster;
  public $target_commit;
  public $target_ref;
  public $watchers;
  public $backups;

  public function __construct(Site $site, $data = null) {
    $this->site = $site;
    if (property_exists($data, 'id')) {
      $this->name = $this->id = $data->id;
    }
    $this->attributes = $data;

    $this->bindings = new Bindings(array('environment' => $this));

    if (is_object($data)) {
      // if we receive an environment object from the api hydrate the vars
      $environment_properties = get_object_vars($data);
      // iterate our local properties setting them where available in the imported object
      foreach (get_object_vars($this) as $key => $value) {
        if(array_key_exists($key,$environment_properties)) {
          $this->$key = $environment_properties[$key];
        }
      }
    }
  }

  /**
   * Deletes all content (files and database) from the Environment
   *
   * @param $args
  **/
  public function wipe() {
    $workflow = $this->site->workflows->create('wipe', array(
      'environment' => $this->id
    ));
    return $workflow;
  }

  public function diffstat() {
    $path = sprintf('environments/%s/on-server-development/diffstat', $this->name);
    $data = \Terminus_Command::request('sites', $this->site->getId(), $path, 'GET');
    return $data['data'];
  }


  /**
   * Create a backup
   *
   * @param $args
  **/
  public function createBackup($args) {
    $type = 'backup';
    if (array_key_exists('type',$args)) {
      $type = $args['type'];
    }

    $ttl = 86400*365;
    if (array_key_exists('keep-for', $args)) {
      $ttl = 86400 * (int) $args['keep-for'];
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
        $args['files'] = true;
        $args['code'] = true;
        $args['database'] = true;
        break;
    }

    $params = array(
      'entry_type' => $type,
      'code' => isset($args['code']),
      'database' => isset($args['database']),
      'files' => isset($args['files']),
      'ttl' => $ttl
    );

    $workflow = $this->site->workflows->create('do_export', array(
      'environment' => $this->id,
      'params' => $params
    ));
    $workflow->wait();

    return $workflow;
  }

  /**
   * @param null $element string -- code, file, db
   * @param bool $latest_only
   * @return array
   */
  public function backups($element = null) {
    if (null === $this->backups) {
      $path = sprintf("environments/%s/backups/catalog", $this->name);
      $response = \Terminus_Command::request('sites', $this->site->getId(), $path, 'GET');
      $this->backups = $response['data'];
    }
    $backups = (array) $this->backups;
    ksort($backups);
    if ($element) {
      $element = $this->element_as_database($element);
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
   * @param $bucket string -- backup folder
   * @param $element string -- files,code,database
   */
  public function backupUrl($bucket, $element) {
    $element = $this->element_as_database($element);
    $path = sprintf("environments/%s/backups/catalog/%s/%s/s3token", $this->name, $bucket, $element);
    $data = array('method'=>'GET');
    $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json') );
    $response = \Terminus_Command::request('sites', $this->site->getId(), $path, 'POST', $options);
    return $response['data'];
  }

  /**
   * Start a work flow
   * @param $workflow string work flow "slot"
   */
  public function workflow($workflow) {
    $path = sprintf("environments/%s/workflows", $this->name);
    $data = array(
      'type' => $workflow,
      'environment' => $this->name,
    );
    $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json'));
    $response = \Terminus_Command::request('sites', $this->site->getId(), $path, 'POST', $options);

    return $response['data'];
  }

  /**
  * OnServer Dev Handler
  *
  * @param $value string optional -- git or sftp, connection mode to set
  * @param $commit string optional -- should be the commit message to use if
  * committing on server changes
  */
  public function onServerDev($value = null, $commit = null ) {
    $path = sprintf("environments/%s/on-server-development", $this->name);
    if ($commit) {
      $path = sprintf("%s/commit", $path);
      $data = ($commit) ? array('message' => $commit, 'user' => Session::getValue('user_uuid')) : NULL;
      $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json'));
      $data = \Terminus_Command::request('sites', $this->site->getId(), $path, 'POST', $options);
    } else {
      if (null == $value) {
        $data = \Terminus_Command::request('sites', $this->site->getId(), $path, 'GET');
      } else {
        $enabled = ($value == 'sftp') ? true : false;
        $data = array(
          'enabled' => $enabled,
        );
        $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json'));
        $data = \Terminus_Command::request('sites', $this->site->getId(), $path, 'PUT', $options);
      }
    }

    if (empty($data)) {
      return false;
    }
    return $data['data'];
  }

  /**
   * Get the code log (commits)
   */
  public function log() {
    $path = sprintf("environments/%s/code-log",$this->name);
    $response = \Terminus_Command::request('sites', $this->site->getId(), $path, 'GET');
    return $response['data'];
  }

  /**
   * Enable HTTP Basic Access authentication on the web environment
   */
  public function lock($options = array()) {
    $username = $options['username'];
    $password = $options['password'];

    $workflow = $this->site->workflows->create('lock_environment', array(
      'environment' => $this->id,
      'params' => array(
        'username' => $username,
        'password' => $password
      )
    ));
    return $workflow;
  }

  /**
   * Disable HTTP Basic Access authentication on the web environment
   */
  public function unlock() {
    $workflow = $this->site->workflows->create('unlock_environment', array(
      'environment' => $this->id,
    ));
    return $workflow;
  }

  /**
   * Get Info on an environment lock
   */
  public function lockinfo() {
    $info = $this->attributes->lock;
    return $info;
  }

  /**
   * list hotnames for environment
   */
  public function hostnames() {
    $response = \Terminus_Command::request("sites", $this->site->getId(), 'environments/' . $this->name . '/hostnames', 'GET');
    return $response['data'];
  }

  /**
   * Add hostname to environment
   */
  public function hostnameadd($hostname) {
    $response = \Terminus_Command::request("sites", $this->site->getId(), 'environments/' . $this->name . '/hostnames/' . rawurlencode($hostname), "PUT");
    return $response['data'];
  }

  /**
   * Delete hostname from environment
   */
  public function hostnamedelete($hostname) {
    $response = \Terminus_Command::request("sites", $this->site->getId(), 'environments/' . $this->name . '/hostnames/' . rawurlencode($hostname), "DELETE");
    return $response['data'];
  }

  /**
   * Generate environment URL
   */
  public function domain() {
    $host = sprintf( "%s-%s.%s", $this->name, $this->site->getName(), $this->dns_zone );
    return $host;
  }

  /**
   * creates a new environment
   *
  */
  public function create($env_name) {
    $path = sprintf('environments/%s', $env_name);
    $OPTIONS = array(
      'headers'=> array('Content-type'=>'application/json')
    );
    $response = \Terminus_Command::request('sites', $site_id, $path, 'POST', $OPTIONS);
    return $response['data'];
  }

  /**
   * "Wake" a site
   */
  public function wake() {
    $hostnames = $this->hostnames();
    $target = key($hostnames);
    $response = Request::send( "http://$target/pantheon_healthcheck", 'GET');
    $return_data = array(
      'success'  => $response->isSuccessful(),
      'time' => $response->getInfo('total_time'),
      'styx' => $response->getHeader('X-Pantheon-Styx-Hostname'),
      'response' => $response,
      'target' => $target,
    );
    return $return_data;
  }

  /**
   * Merge code from the Dev Environment into this Multidev Environment
   *
   */
  public function mergeFromDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new Exception(sprintf("The %s environment is not a multidev environment", $this->id));
    }

    $default_params = array(
      'updatedb' => false
    );
    $params = array_merge($default_params, $options);

    $workflow = $this->site->workflows->create('merge_dev_into_cloud_development_environment', array(
      'environment' => $this->id,
      'params' => $params
    ));

    return $workflow;
  }

  /**
   * Merge code from this Multidev Environment into the Dev Environment
   *
   */
  public function mergeToDev($options = array()) {
    if (!$this->isMultidev()) {
      throw new Exception(sprintf("The %s environment is not a multidev environment", $this->id));
    }

    $default_params = array(
      'updatedb' => false
    );
    $params = array_merge($default_params, $options);

    // This function is a little odd because we invoke it on a
    // multidev environment, but it applies a workflow to the 'dev' environment
    $params['from_environment'] = $this->id;
    $workflow = $this->site->workflows->create('merge_cloud_development_environment_into_dev', array(
      'environment' => 'dev',
      'params' => $params
    ));

    return $workflow;
  }

  public function connectionInfo() {
    $this->bindings->fetch();
    $info = array();

    // Can only SFTP into dev/multidev environments
    if (!in_array($this->id, array('test', 'live'))) {
      $sftp_username = sprintf("%s.%s", $this->id, $this->site->getId());
      $sftp_password = "Use your account password";
      $sftp_host = sprintf("appserver.%s.%s.drush.in", $this->id, $this->site->getId());
      $sftp_port = 2222;

      $sftp_url = sprintf('sftp://%s@%s:%s', $sftp_username, $sftp_host, $sftp_port);
      $sftp_command = sprintf('sftp -o Port=%s %s@%s', $sftp_port, $sftp_username, $sftp_host);

      $info = array_merge($info, array(
        'sftp_username' => $sftp_username,
        'sftp_host'     => $sftp_host,
        'sftp_password' => $sftp_password,
        'sftp_url'      => $sftp_url,
        'sftp_command'  => $sftp_command
      ));
    }

    $git_username = sprintf("codeserver.%s.%s", $this->id, $this->site->getId());
    $git_host = sprintf("codeserver.%s.%s.drush.in", $this->id, $this->site->getId());
    $git_port = 2222;

    $git_url = sprintf("git://%s@%s:%s", $git_username, $git_host, $git_port);
    $git_command = sprintf("git clone %s %s", $git_url, $this->site->getName());

    $info = array_merge($info, array(
      'git_username' => $git_username,
      'git_host'     => $git_host,
      'git_port'     => $git_port,
      'git_url'      => $git_url,
      'git_command'  => $git_command
    ));

    if (isset($this->bindings->getByType('dbserver')[0])) {
      $db_binding = $this->bindings->getByType('dbserver')[0];

      $mysql_username = 'pantheon';
      $mysql_password = $db_binding->get('password');
      $mysql_host = sprintf("dbserver.%s.%s.drush.in", $this->id, $this->site->getId());
      $mysql_port = $db_binding->get('port');
      $mysql_database = 'pantheon';

      $mysql_url = sprintf('mysql://%s:%s@%s:%s/%s',
        $mysql_username,
        $mysql_password,
        $mysql_host,
        $mysql_port,
        $mysql_database
      );
      $mysql_command = sprintf('mysql -u %s -p%s -h %s -P %s %s',
        $mysql_username,
        $mysql_password,
        $mysql_host,
        $mysql_port,
        $mysql_database
      );

      $info = array_merge($info, array(
        'mysql_host'     => $mysql_host,
        'mysql_username' => $mysql_username,
        'mysql_password' => $mysql_password,
        'mysql_port'     => $mysql_port,
        'mysql_database' => $mysql_database,
        'mysql_url'      => $mysql_url,
        'mysql_command'  => $mysql_command
      ));
    }

    if (isset($this->bindings->getByType('cacheserver')[0])) {
      $cache_binding = $this->bindings->getByType('cacheserver')[0];

      $redis_password = $cache_binding->get('password');
      $redis_host = $mysql_host = sprintf("cacheserver.%s.%s.drush.in", $this->id, $this->site->getId());
      $redis_port = $cache_binding->get('port');

      $redis_url = sprintf('redis://pantheon:%s@%s:%s',
        $redis_password,
        $redis_host,
        $redis_port
      );
      $redis_command = sprintf('redis-cli -h %s -p %s -a %s',
        $redis_host,
        $redis_port,
        $redis_password
      );

      $info = array_merge($info, array(
        'redis_password' => $redis_password,
        'redis_host'     => $redis_host,
        'redis_port'     => $redis_port,
        'redis_url'      => $redis_url,
        'redis_command'  => $redis_command
      ));
    }

    return $info;
  }

  /**
   * Returns its argument unless that argument is "db", then returns "database"
   * @param element $string -- Represents the request element
   * @return string
   */
  private function element_as_database($element) {
    if ($element == 'db') {
      return 'database';
    }
    return $element;
  }

  /**
   * Deploys the Test or Live environment
   *
   * @param [array] $params Parameters for the deploy workflow
   *
   * @return [workflow] workflow response
   */
  public function deploy($params) {
    $workflow = $this->site->workflows->create('deploy', array(
      'environment' => $this->id,
      'params'      => $params
    ));
    return $workflow;
  }

  /**
   * Have the environment's bindings have been initialized?
   *
   * @return [boolean] whether the environment has been initialized or not
   */
  public function isInitialized() {
    // One can determine whether an environment has been initialized
    // by checking if it has code commits. Unitialized environments do not.
    $commits = $this->log();
    $has_commits = (count($commits) > 0);
    return $has_commits;
  }

  /**
   * Initializes the Test/Live environments on a newly created Site
   * and clones content from previous environment
   * (e.g. Test clones Dev content, Live clones Test content)
   *
   * @return [Workflow] in-progress workflow
   */
  public function initializeBindings() {
    if ($this->id == 'test') {
      $from_env_id = 'dev';
    } elseif ($this->id == 'live') {
      $from_env_id = 'test';
    }

    $workflow = $this->site->workflows->create('deploy', array(
      'environment' => $this->id,
      'params' => array(
        'annotation' => sprintf('Create the %s environment', $this->id),
        'clone_database' => array('from_environment' => $from_env_id),
        'clone_files' => array('from_environment' => $from_env_id)
      )
    ));
    return $workflow;
  }

  /**
   * Returns the environment's name
   *
   * @return [string] $this->name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Is this Branch a multidev environment
   */
  public function isMultidev() {
    $is_multidev = !in_array($this->id, array('dev', 'test', 'live'));
    return $is_multidev;
  }
}
