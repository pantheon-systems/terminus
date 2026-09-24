<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays all Redis server information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays all Redis server information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function info($site_env)
    {
        return $this->getRedisInfo($site_env);
    }

    /**
     * Displays Redis memory information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:memory
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis memory information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoMemory($site_env)
    {
        return $this->getRedisInfo($site_env, 'memory');
    }

    /**
     * Displays Redis CPU information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:cpu
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis CPU information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoCpu($site_env)
    {
        return $this->getRedisInfo($site_env, 'cpu');
    }

    /**
     * Displays Redis statistics.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:stats
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis statistics for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoStats($site_env)
    {
        return $this->getRedisInfo($site_env, 'stats');
    }

    /**
     * Displays Redis replication information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:replication
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis replication information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoReplication($site_env)
    {
        return $this->getRedisInfo($site_env, 'replication');
    }

    /**
     * Displays Redis client information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:clients
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis client information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoClients($site_env)
    {
        return $this->getRedisInfo($site_env, 'clients');
    }

    /**
     * Displays Redis server information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:server
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis server information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoServer($site_env)
    {
        return $this->getRedisInfo($site_env, 'server');
    }

    /**
     * Displays Redis persistence information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:persistence
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis persistence information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoPersistence($site_env)
    {
        return $this->getRedisInfo($site_env, 'persistence');
    }

    /**
     * Displays Redis keyspace information.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:keyspace
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis keyspace information for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoKeyspace($site_env)
    {
        return $this->getRedisInfo($site_env, 'keyspace');
    }

    /**
     * Displays Redis command statistics.
     *
     * @authorize
     * @interact
     *
     * @command redis:info:commandstats
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays Redis command statistics for <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function infoCommandstats($site_env)
    {
        return $this->getRedisInfo($site_env, 'commandstats');
    }

    /**
     * Helper method to get Redis INFO for a specific section.
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $section Optional section name
     *
     * @throws TerminusException
     */
    protected function getRedisInfo($site_env, $section = '')
    {
        $env = $this->getEnv($site_env);

        $this->log()->notice(
            'Retrieving Redis connection information for {site}.{env}...',
            ['site' => $env->getSite()->getName(), 'env' => $env->getName()]
        );

        $connection_info = $env->connectionInfo();

        if (!isset($connection_info['redis_command'])) {
            throw new TerminusException(
                'Redis is not enabled for {site}.{env}. Please enable Redis in the Site Dashboard.',
                ['site' => $env->getSite()->getName(), 'env' => $env->getName()]
            );
        }

        $redis_command = $connection_info['redis_command'];

        // Build the INFO command with optional section
        // Note: Section is a known Redis INFO section name (memory, cpu, etc.)
        // so we can safely append it without shell escaping
        if ($section) {
            $command = sprintf('%s INFO %s', $redis_command, $section);
        } else {
            $command = sprintf('%s INFO', $redis_command);
        }

        $this->log()->notice('Fetching Redis information...');

        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new TerminusException(
                'Redis INFO command failed with exit code {code}',
                ['code' => $return_var]
            );
        }

        // Output the results
        foreach ($output as $line) {
            $this->output()->writeln($line);
        }
    }
}
