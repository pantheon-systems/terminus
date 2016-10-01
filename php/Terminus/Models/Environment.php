<?php

namespace Terminus\Models;

use GuzzleHttp\TransferStats as TransferStats;
use Terminus\Collections\Backups;
use Terminus\Collections\Bindings;
use Terminus\Collections\Commits;
use Terminus\Collections\Hostnames;
use Terminus\Collections\Workflows;
use Terminus\Config;
use Terminus\Exceptions\TerminusException;

class Environment extends TerminusModel
{
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
    public function __construct($attributes, array $options = [])
    {
        $this->site = $options['collection']->site;
        parent::__construct($attributes, $options);
        $options = ['environment' => $this,];
        $this->backups = new Backups($options);
        $this->bindings = new Bindings($options);
        $this->commits = new Commits($options);
        $this->hostnames = new Hostnames($options);
        $this->workflows = new Workflows($options);
        $this->url = "sites/{$this->site->id}/environments/{$this->id}";
    }

  /**
   * Apply upstream updates
   *
   * @param boolean $updatedb True to run update.php
   * @param boolean $xoption  True to automatically resolve merge conflicts
   * @return Workflow
   */
    public function applyUpstreamUpdates($updatedb = true, $xoption = false)
    {
        $params = ['updatedb' => $updatedb, 'xoption' => $xoption];
        $workflow = $this->workflows->create('apply_upstream_updates', compact('params'));
        return $workflow;
    }

  /**
   * Gives cacheserver connection info for this environment
   *
   * @return array
   */
    public function cacheserverConnectionInfo()
    {
        $info = [];
        $cacheserver_binding = (array)$this->bindings->getByType('cacheserver');
        if (!empty($cacheserver_binding)) {
            do {
                $next_binding = array_shift($cacheserver_binding);
                if (is_null($next_binding)) {
                    break;
                }
                $cache_binding = $next_binding;
            } while (!is_null($cache_binding) && $cache_binding->get('environment') != $this->id);

            $password = $cache_binding->get('password');
            $hostname = $cache_binding->get('host');
            $port = $cache_binding->get('port');
            $url = "redis://pantheon:$password@$hostname:$port";
            $command = "redis-cli -h $hostname -p $port -a $password";
            $info = [
            'password' => $password,
            'host' => $hostname,
            'port' => $port,
            'url' => $url,
            'command' => $command,
            ];
        }
        return $info;
    }

  /**
   * Changes connection mode
   *
   * @param string $value Connection mode, "git" or "sftp"
   * @return Workflow|string
   */
    public function changeConnectionMode($value)
    {
        $current_mode = $this->serialize()['connection_mode'];
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
    public function clearCache()
    {
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
    public function cloneDatabase($from_env)
    {
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
    public function cloneFiles($from_env)
    {
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
    public function commitChanges($commit = null)
    {
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
    public function connectionInfo()
    {
        $sftp_info = $this->sftpConnectionInfo();
        $mysql_info = $this->databaseConnectionInfo();
        $redis_info = $this->cacheserverConnectionInfo();
        $info = array_merge(
            array_combine(
                array_map(function ($key) {
                    return "sftp_$key";
                }, array_keys($sftp_info)),
                array_values($sftp_info)
            ),
            array_combine(
                array_map(function ($key) {
                    return "mysql_$key";
                }, array_keys($mysql_info)),
                array_values($mysql_info)
            ),
            array_combine(
                array_map(function ($key) {
                    return "redis_$key";
                }, array_keys($redis_info)),
                array_values($redis_info)
            )
        );

      // Can only Use Git on dev/multidev environments
        if (!in_array($this->id, ['test', 'live',])) {
            $git_info = $this->gitConnectionInfo();
            $info = array_merge(
                array_combine(
                    array_map(function ($key) {
                        return "git_$key";
                    }, array_keys($git_info)),
                    array_values($git_info)
                ),
                $info
            );
        }

        return $info;
    }

  /**
   * Converges all bindings on a site
   *
   * @return array
   */
    public function convergeBindings()
    {
        $workflow = $this->workflows->create('converge_environment');
        return $workflow;
    }

  /**
   * Counts the number of deployable commits
   *
   * @return int
   */
    public function countDeployableCommits()
    {
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
   * Gives database connection info for this environment
   *
   * @return array
   */
    public function databaseConnectionInfo()
    {
        $dbserver_binding = (array)$this->bindings->getByType('dbserver');
        if (!empty($dbserver_binding)) {
            do {
                $db_binding = array_shift($dbserver_binding);
            } while ($db_binding->get('environment') != $this->id);

            $username = 'pantheon';
            $password = $db_binding->get('password');
            $hostname = "dbserver.{$this->id}.{$this->site->id}.drush.in";
            $port = $db_binding->get('port');
            $database = 'pantheon';
            $url = "mysql://$username:$password@$hostname:$port/$database";
            $command  = "mysql -u $username -p$password -h $hostname -P $port $database";
            $info = [
            'host'     => $hostname,
            'username' => $username,
            'password' => $password,
            'port'     => $port,
            'database' => $database,
            'url'      => $url,
            'command'  => $command,
            ];
        }
        return $info;
    }

  /**
   * Delete a multidev environment
   *
   * @param array $arg_options Elements as follow:
   *   bool delete_branch True to delete branch
   * @return Workflow
   */
    public function delete(array $arg_options = [])
    {
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
    public function deploy($params)
    {
        $workflow = $this->workflows->create('deploy', compact('params'));
        return $workflow;
    }

  /**
   * Gets diff from multidev environment
   *
   * @return array
   */
    public function diffstat()
    {
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
    public function domain()
    {
        return "{$this->id}-{$this->site->get('name')}.{$this->get('dns_zone')}";
    }

  /**
   * Gets the Drush version of this environment
   *
   * @return int
   */
    public function getDrushVersion()
    {
        $version = (integer)$this->settings('drush_version');
        return $version;
    }

  /**
   * Returns the environment's name
   *
   * @return string
   */
    public function getName()
    {
        return $this->id;
    }

  /**
   * Returns the parent environment
   *
   * @return Environment
   */
    public function getParentEnvironment()
    {
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
   * Gives Git connection info for this environment
   *
   * @return array
   */
    public function gitConnectionInfo()
    {
        $username = "codeserver.dev.{$this->site->id}";
        $hostname = "codeserver.dev.{$this->site->id}.drush.in";
        $port = '2222';
        $url = "ssh://$username@$hostname:$port/~/repository.git";
        $command  = "git clone $url {$this->site->get('name')}";
        $info = [
        'username' => $username,
        'host' => $hostname,
        'port' => $port,
        'url' => $url,
        'command' => $command,
        ];
        return $info;
    }

  /**
   * Decides if the environment has changes to deploy
   *
   * @return bool
   */
    public function hasDeployableCode()
    {
        $number_of_commits = $this->countDeployableCommits();
        return (boolean)$number_of_commits;
    }

  /**
   * Imports a database archive
   *
   * @param string $url URL to import data from
   * @return Workflow
   */
    public function importDatabase($url)
    {
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
    public function import($url)
    {
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
    public function importFiles($url)
    {
        $workflow = $this->workflows->create(
            'import_files',
            ['params' => compact('url'),]
        );
        return $workflow;
    }

  /**
   * Initializes the test/live environments on a newly created site  and clones
   * content from previous environment (e.g. test clones dev content, live
   * clones test content.)
   *
   * @return Workflow In-progress workflow
   */
    public function initializeBindings()
    {
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
    public function isInitialized()
    {
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
    public function isMultidev()
    {
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
    public function lock($params)
    {
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
    public function lockinfo()
    {
        $lock = $this->get('lock');
        return $lock;
    }

  /**
   * Merge code from the Dev Environment into this Multidev Environment
   *
   * @param array $options Parameters to override defaults
   *        boolean updatedb True to update DB with merge
   * @return Workflow
   * @throws TerminusException
   */
    public function mergeFromDev($options = [])
    {
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
    public function mergeToDev(array $options = [])
    {
        if ($this->id != 'dev') {
            throw new TerminusException('Environment::mergeToDev() may only be run on the dev environment.');
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
     * Sends a command to an environment via SSH.
     *
     * @param string $command The command to be run on the platform
     * @return string[] $response Elements as follow:
     *         string output    The output from the command run
     *         string exit_code The status code returned by the command run
     */
    public function sendCommandViaSsh($command)
    {
        $sftp = $this->sftpConnectionInfo();
        $ssh_command = vsprintf(
            'ssh -T %s@%s -p %s -o "AddressFamily inet" %s',
            [$sftp['username'], $sftp['host'], $sftp['port'], escapeshellarg($command),]
        );

        // Catch Terminus running in test mode
        if (Config::get('test_mode')) {
            return [
                'output'    => "Terminus is in test mode. "
                    . "Environment::sendCommandViaSsh commands will not be sent over the wire. "
                    . "SSH Command: ${ssh_command}",
                'exit_code' => 255
            ];
        }

        ob_start();
        passthru($ssh_command, $exit_code);
        $response = ['output' => ob_get_clean(), 'exit_code' => $exit_code,];

        return $response;
    }

    /**
     * Formats environment object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        $info = [
          'id' => $this->id,
          'created' => date(Config::get('date_format'), $this->get('environment_created')),
          'domain' => $this->domain(),
          'onserverdev' => $this->get('on_server_development') ? 'true' : 'false',
          'locked' => $this->get('lock')->locked ? 'true' : 'false',
          'initialized' => $this->isInitialized() ? 'true' : 'false',
          'connection_mode' => $this->get('connection_mode'),
          'php_version' => $this->get('php_version'),
        ];
        return $info;
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
    public function setHttpsCertificate($certificate = [])
    {
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
   * Gives SFTP connection info for this environment
   *
   * @return array
   */
    public function sftpConnectionInfo()
    {
        if (!empty($ssh_host = Config::get('ssh_host'))) {
            $username = "appserver.{$this->id}.{$this->site->id}";
            $hostname = $ssh_host;
        } elseif (strpos(Config::get('host'), 'onebox') !== false) {
            $username = "appserver.{$this->id}.{$this->site->id}";
            $hostname = Config::get('host');
        } else {
            $username = "{$this->id}.{$this->site->id}";
            $hostname = "appserver.{$this->id}.{$this->site->id}.drush.in";
        }
        $password = 'Use your account password';
        $port = '2222';
        $url = "sftp://$username@$hostname:$port";
        $command = "sftp -o Port=$port $username@$hostname";
        $info = [
        'username' => $username,
        'host' => $hostname,
        'port' => $port,
        'password' => $password,
        'url' => $url,
        'command'  => $command,
        ];
        return $info;
    }

  /**
   * Disable HTTP Basic Access authentication on the web environment
   *
   * @return Workflow
   */
    public function unlock()
    {
        $workflow = $this->workflows->create('unlock_environment');
        return $workflow;
    }

  /**
   * "Wake" a site
   *
   * @return array
   */
    public function wake()
    {
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
    public function wipe()
    {
        $workflow = $this->workflows->create('wipe');
        return $workflow;
    }

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     * @return object $data
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'on_server_development')
          && (boolean)$data->on_server_development
        ) {
            $data->connection_mode = 'sftp';
        } else {
            $data->connection_mode = 'git';
        }
        if (property_exists($data, 'php_version')) {
            $data->php_version = substr($data->php_version, 0, 1) . '.' . substr($data->php_version, 1, 1);
        } else {
            $data->php_version = $this->site->get('php_version');
        }
        return $data;
    }

  /**
   * Retrieves the value of an environmental setting
   *
   * @param string $setting Name of the setting to retrieve
   * @return mixed
   */
    private function settings($setting = null)
    {
        $path = "sites/{$this->site->id}/environments/{$this->id}/settings";
        $response = (array)$this->request->request($path, ['method' => 'get',]);
        if (property_exists($response['data'], $setting)) {
            return $response['data']->$setting;
        }
        return (array)$response['data'];
    }
}
