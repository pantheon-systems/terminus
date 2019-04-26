<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Collections\Bindings;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\EnvironmentMetrics;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Environment
 * @package Pantheon\Terminus\Models
 */
class Environment extends TerminusModel implements ContainerAwareInterface, SiteInterface
{
    use ContainerAwareTrait;
    use SiteTrait;

    const PRETTY_NAME = 'environment';

    /**
     * @var array
     */
    public static $date_attributes = ['created',];
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{id}';
    /**
     * @var Backups
     */
    private $backups;
    /**
     * @var Bindings
     */
    private $bindings;
    /**
     * @var Commits
     */
    private $commits;
    /**
     * @var Domains
     */
    private $domains;
    /**
     * @var Lock
     */
    private $lock;
    /**
     * @var UpstreamStatus
     */
    private $upstream_status;
    /**
     * @var Workflows
     */
    private $workflows;

    /**
     * Apply upstream updates
     *
     * @param boolean $updatedb True to run update.php
     * @param boolean $xoption True to automatically resolve merge conflicts
     * @return Workflow
     */
    public function applyUpstreamUpdates($updatedb = true, $xoption = false)
    {
        $params = ['updatedb' => $updatedb, 'xoption' => $xoption];
        return $this->getWorkflows()->create('apply_upstream_updates', compact('params'));
    }

    /**
     * Gives cacheserver connection info for this environment
     *
     * @return array
     */
    public function cacheserverConnectionInfo()
    {
        $cacheserver_binding = array_filter((array)$this->getBindings()->getByType('cacheserver'));
        if (!empty($cacheserver_binding)) {
            do {
                $cache_binding = array_shift($cacheserver_binding);
            } while (!is_null($cache_binding) && $cache_binding->get('environment') != $this->id);

            $password = $cache_binding->get('password');
            $domain = $cache_binding->get('host');
            $port = $cache_binding->get('port');
            $url = "redis://pantheon:$password@$domain:$port";
            $command = "redis-cli -h $domain -p $port -a $password";
            return [
                'password' => $password,
                'host' => $domain,
                'port' => $port,
                'url' => $url,
                'command' => $command,
            ];
        }
        return [];
    }

    /**
     * Changes connection mode
     *
     * @param string $value Connection mode, "git" or "sftp"
     * @return Workflow
     * @throws TerminusException Thrown when the requested or the mode is already set or is not either "git" or "sftp".
     */
    public function changeConnectionMode($mode)
    {
        if ($mode === $this->get('connection_mode')) {
            throw new TerminusException(
                'The connection mode is already set to {mode}.',
                compact('mode')
            );
        }
        switch ($mode) {
            case 'git':
                $workflow_name = 'enable_git_mode';
                break;
            case 'sftp':
                $workflow_name = 'enable_on_server_development';
                break;
            default:
                throw new TerminusException('You must specify the mode as either sftp or git.');
        }

        return $this->getWorkflows()->create($workflow_name);
    }

    /**
     * Clears an environment's cache
     *
     * @return Workflow
     */
    public function clearCache()
    {
        return $this->getWorkflows()->create('clear_cache', ['params' => ['framework_cache' => true,],]);
    }

    /**
     * Clones database from this environment to another
     *
     * @param Environment $from_env An object representing the environment to clone
     * @param array $options Options to be sent to the API
     *    boolean clear_cache Whether or not to clear caches
     *    boolean updatedb Update the Drupal database
     * @return Workflow
     */
    public function cloneDatabase(Environment $from_env, array $options = [])
    {
        if (isset($options['updatedb'])) {
            $options['updatedb'] = (integer)$options['updatedb'];
        }
        $params = array_merge(['from_environment' => $from_env->getName(),], $options);
        return $this->getWorkflows()->create('clone_database', compact('params'));
    }

    /**
     * Clones files from this environment to another
     *
     * @param Environment $from_env An object representing the environment to clone
     * @return Workflow
     */
    public function cloneFiles(Environment $from_env)
    {
        $params = ['from_environment' => $from_env->getName(),];
        return $this->getWorkflows()->create('clone_files', compact('params'));
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
        $local = $this->getContainer()->get(LocalMachineHelper::class);

        $git_email_result = $local->exec('git config user.email');
        $git_user_result = $local->exec('git config user.name');

        $params = [
            'message' => $commit,
            'committer_name' => $git_user_result['output'],
            'committer_email' => $git_email_result['output'],
        ];

        return $this->getWorkflows()->create('commit_and_push_on_server_changes', compact('params'));
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
        return $this->getWorkflows()->create('converge_environment');
    }

    /**
     * Counts the number of deployable commits
     *
     * @return int
     */
    public function countDeployableCommits()
    {
        $parent_environment = $this->getParentEnvironment();
        $parent_commits = $parent_environment->getCommits()->all();
        $number_of_commits = 0;
        foreach ($parent_commits as $commit) {
            $labels = $commit->get('labels');
            $number_of_commits += (integer)(
                !in_array($this->id, $labels)
                && in_array($parent_environment->id, $labels)
            );
        }
        return $number_of_commits;
    }

    /**
     * Provides Pantheon Dashboard URL for this environment
     *
     * @return string
     */
    public function dashboardUrl()
    {
        return "{$this->getSite()->dashboardUrl()}#{$this->id}";
    }

    /**
     * Gives database connection info for this environment
     *
     * @return array
     */
    public function databaseConnectionInfo()
    {
        $dbserver_binding = array_filter((array)$this->getBindings()->getByType('dbserver'));
        if (!empty($dbserver_binding)) {
            do {
                $db_binding = array_shift($dbserver_binding);
            } while ($db_binding->get('environment') != $this->id);

            $username = 'pantheon';
            $password = $db_binding->get('password');
            $domain = "dbserver.{$this->id}.{$this->getSite()->id}.drush.in";
            $port = $db_binding->get('port');
            $database = 'pantheon';
            $url = "mysql://$username:$password@$domain:$port/$database";
            $command = "mysql -u $username -p$password -h $domain -P $port $database";
            return [
                'host' => $domain,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'database' => $database,
                'url' => $url,
                'command' => $command,
            ];
        }
        return [];
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
        $options = array_merge($default_options, $arg_options);
        $params = array_merge(
            ['environment_id' => $this->id,],
            $options
        );
        return $this->getSite()->getWorkflows()->create(
            'delete_cloud_development_environment',
            compact('params')
        );
    }

    /**
     * Deploys the Test or Live environment
     *
     * @param array $params Parameters for the deploy workflow
     * @return Workflow
     */
    public function deploy($params)
    {
        return $this->getWorkflows()->create('deploy', compact('params'));
    }

    /**
     * Gets diff from multidev environment
     *
     * @return array
     */
    public function diffstat()
    {
        $path = sprintf(
            'sites/%s/environments/%s/on-server-development/diffstat',
            $this->getSite()->id,
            $this->id
        );
        $options = ['method' => 'get',];
        $data = $this->request()->request($path, $options);
        return $data['data'];
    }

    /**
     * Remove a HTTPS certificate from the environment
     *
     * @return array $workflow
     *
     * @throws TerminusException
     */
    public function disableHttpsCertificate()
    {
        if (!$this->settings('ssl_enabled')) {
            throw new TerminusException('The {env} environment does not have https enabled.', ['env' => $this->id,]);
        }
        try {
            $this->request()->request(
                "sites/{$this->getSite()->id}/environments/{$this->id}/settings",
                [
                    'method' => 'put',
                    'form_params' => [
                        'ssl_enabled' => false,
                        'dedicated_ip' => false,
                    ],
                ]
            );
        } catch (\Exception $e) {
            throw new TerminusException('There was an problem disabling https for this environment.');
        }
    }

    /**
     * Generate environment URL
     *
     * @return string
     */
    public function domain()
    {
        $site = $this->getSite()->getName();
        return "{$this->id}-$site.{$this->get('dns_zone')}";
    }

    /**
     * @return Backups
     */
    public function getBackups()
    {
        if (empty($this->backups)) {
            $this->backups = $this->getContainer()->get(Backups::class, [['environment' => $this,],]);
        }
        return $this->backups;
    }

    /**
     * @return Bindings
     */
    public function getBindings()
    {
        if (empty($this->bindings)) {
            $this->bindings = $this->getContainer()->get(Bindings::class, [['environment' => $this,],]);
        }
        return $this->bindings;
    }

    /**
     * @return string
     */
    public function getBranchName()
    {
        return $this->isMultidev() ? $this->id : 'master';
    }

    /**
     * @return Commits
     */
    public function getCommits()
    {
        if (empty($this->commits)) {
            $this->commits = $this->getContainer()->get(Commits::class, [['environment' => $this,],]);
        }
        return $this->commits;
    }

    /**
     * @return EnvironmentMetrics
     */
    public function getEnvironmentMetrics()
    {
        if (empty($this->environment_metrics)) {
            $this->environment_metrics = $this->getContainer()->get(EnvironmentMetrics::class, [['environment' => $this,],]);
        }
        return $this->environment_metrics;
    }

    /**
     * @return Domains
     */
    public function getDomains()
    {
        if (empty($this->domains)) {
            $this->domains = $this->getContainer()->get(Domains::class, [['environment' => $this,],]);
        }
        return $this->domains;
    }

    /**
     * Gets the Drush version of this environment
     *
     * @return string
     */
    public function getDrushVersion()
    {
        return $this->settings('drush_version');
    }

    /**
     * Returns the lock object associated with this environment
     *
     * @return Lock
     */
    public function getLock()
    {
        if (empty($this->lock)) {
            $this->lock = $this->getContainer()->get(Lock::class, [$this->get('lock'), ['environment' => $this],]);
        }
        return $this->lock;
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
        return $this->getSite()->getEnvironments()->get($parent_env_id);
    }

    /**
     * Returns the PHP version of this environment.
     *
     * @return null|string
     */
    public function getPHPVersion()
    {
        return !is_null($php_ver = $this->get('php_version')) ? substr($php_ver, 0, 1) . '.' . substr($php_ver, 1) : null;
    }

    /**
     * @return UpstreamStatus
     */
    public function getUpstreamStatus()
    {
        if (empty($this->upstream_status)) {
            $this->upstream_status = $this->getContainer()->get(UpstreamStatus::class, [[], ['environment' => $this,],]);
        }
        return $this->upstream_status;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = $this->getContainer()->get(Workflows::class, [['environment' => $this,],]);
        }
        return $this->workflows;
    }

    /**
     * Gives Git connection info for this environment
     *
     * @return array
     */
    public function gitConnectionInfo()
    {
        $site = $this->getSite();
        $username = "codeserver.dev.{$site->id}";
        $domain = "codeserver.dev.{$site->id}.drush.in";
        $port = '2222';
        $url = "ssh://$username@$domain:$port/~/repository.git";
        $command = trim("git clone $url {$site->get('name')}");
        return [
            'username' => $username,
            'host' => $domain,
            'port' => $port,
            'url' => $url,
            'command' => $command,
        ];
    }

    /**
     * Decides if the environment has changes to deploy
     *
     * @return bool
     */
    public function hasDeployableCode()
    {
        return (boolean)$this->countDeployableCommits();
    }

    /**
     * Determines whether there is uncommitted code on the environment.
     *
     * @return bool
     */
    public function hasUncommittedChanges()
    {
        return ($this->get('connection_mode') === 'sftp') && (count((array)$this->get('diffstat')) !== 0);
    }

    /**
     * Imports a database archive
     *
     * @param string $url URL to import data from
     * @return Workflow
     */
    public function importDatabase($url)
    {
        return $this->getWorkflows()->create(
            'do_import',
            [
                'params' => [
                    'database' => 1,
                    'url' => $url,
                ],
            ]
        );
    }

    /**
     * Imports a site archive onto Pantheon
     *
     * @param string $url URL of the archive to import
     * @return Workflow
     */
    public function import($url)
    {
        $workflow = $this->getWorkflows()->create(
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
        return $this->getWorkflows()->create(
            'do_import',
            [
                'params' => [
                    'files' => 1,
                    'url' => $url,
                ],
            ]
        );
    }

    /**
     * Initializes the test/live environments on a newly created site  and clones
     * content from previous environment (e.g. test clones dev content, live
     * clones test content.)
     *
     * @param array $params Parameters for the environment-creation workflow
     *      string annotation Use to overwrite the default deploy message
     * @return Workflow In-progress workflow
     */
    public function initializeBindings(array $params = [])
    {
        if ($this->id == 'test') {
            $from_env_id = 'dev';
        } elseif ($this->id == 'live') {
            $from_env_id = 'test';
        }

        $parameters = array_merge(
            [
                'annotation' => "Create the {$this->id} environment",
                'clone_database' => ['from_environment' => $from_env_id,],
                'clone_files' => ['from_environment' => $from_env_id,],
            ],
            $params
        );

        return $this->getWorkflows()->create('create_environment', ['params' => $parameters,]);
    }

    /**
     * Is this branch a development environment?
     *
     * @return bool True if ths environment is a development environment
     */
    public function isDevelopment()
    {
        return !in_array($this->id, ['test', 'live',]);
    }

    /**
     * Have the environment's bindings have been initialized?
     *
     * @return bool True if environment has been instantiated
     */
    public function isInitialized()
    {
        // Only test or live environments can be uninitialized
        if (!in_array($this->id, ['test', 'live',])) {
            return true;
        }
        // One can determine whether an environment has been initialized
        // by checking if it has code commits. Uninitialized environments do not.
        $commits = $this->getCommits()->all();
        return (count($commits) > 0);
    }

    /**
     * Is this branch a multidev environment?
     *
     * @return bool True if ths environment is a multidev environment
     */
    public function isMultidev()
    {
        return !in_array($this->id, ['dev', 'test', 'live',]);
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
        $params = array_merge($default_params, $options);

        return $this->getWorkflows()->create('merge_dev_into_cloud_development_environment', compact('params'));
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

        return $this->getWorkflows()->create('merge_cloud_development_environment_into_dev', compact('params'));
    }

    /**
     * Formats environment object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return [
            'id' => $this->id,
            'created' => $this->get('environment_created'),
            'domain' => $this->domain(),
            'onserverdev' => $this->get('on_server_development'),
            'locked' => $this->getLock()->isLocked(),
            'initialized' => $this->isInitialized(),
            'connection_mode' => $this->get('connection_mode'),
            'php_version' => $this->getPHPVersion(),
        ];
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
        $response = $this->request()->request(
            "sites/{$this->getSite()->id}/environments/{$this->id}/add-ssl-cert",
            ['method' => 'POST', 'form_params' => array_filter($certificate),]
        );

        // The response is actually a workflow
        return $this->getContainer()->get(Workflow::class, [$response['data'], ['environment' => $this,],]);
    }

    /**
     * Gives SFTP connection info for this environment
     *
     * @return array
     */
    public function sftpConnectionInfo()
    {
        $site = $this->getSite();
        if (!empty($ssh_host = $this->getConfig()->get('ssh_host'))) {
            $username = "appserver.{$this->id}.{$site->id}";
            $domain = $ssh_host;
        } elseif (strpos($this->getConfig()->get('host'), 'onebox') !== false) {
            $username = "appserver.{$this->id}.{$site->id}";
            $domain = $this->getConfig()->get('host');
        } else {
            $username = "{$this->id}.{$site->id}";
            $domain = "appserver.{$this->id}.{$site->id}.drush.in";
        }
        $password = 'Use your account password';
        $port = '2222';
        $url = "sftp://$username@$domain:$port";
        $command = "sftp -o Port=$port $username@$domain";
        return [
            'username' => $username,
            'host' => $domain,
            'port' => $port,
            'password' => $password,
            'url' => $url,
            'command' => $command,
        ];
    }

    /**
     * "Wake" a site
     *
     * @return array
     */
    public function wake()
    {
        $domains = array_filter(
            $this->getDomains()->all(),
            function ($domain) {
                return !empty($domain->get('dns_zone_name'));
            }
        );
        $domain = array_pop($domains);
        $response = $this->request()->request("http://{$domain->id}/pantheon_healthcheck");
        return [
            'success' => ($response['status_code'] === 200),
            'styx' => $response['headers']['X-Pantheon-Styx-Hostname'],
            'response' => $response,
            'target' => $domain->id,
        ];
    }

    /**
     * Deletes all content (files and database) from the Environment
     *
     * @return Workflow
     */
    public function wipe()
    {
        return $this->getWorkflows()->create('wipe');
    }

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     * @return object $data
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'on_server_development') && (boolean)$data->on_server_development) {
            $data->connection_mode = 'sftp';
        } else {
            $data->connection_mode = 'git';
        }
        if (!property_exists($data, 'php_version')) {
            $data->php_version = $this->getSite()->get('php_version');
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
        $path = "sites/{$this->getSite()->id}/environments/{$this->id}/settings";
        $response = (array)$this->request()->request($path, ['method' => 'get',]);
        return $response['data']->$setting;
    }
}
