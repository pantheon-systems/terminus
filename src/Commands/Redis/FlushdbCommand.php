<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class FlushdbCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class FlushdbCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Flushes all keys from the Redis database.
     *
     * @authorize
     * @interact
     *
     * @command redis:flushdb
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Flushes all keys from <site>'s <env> Redis database.
     * @usage <site>.<env> --yes Flushes all keys without confirmation.
     *
     * @throws TerminusException
     */
    public function flushdb($site_env)
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

        if (!$this->input()->getOption('yes')) {
            $this->log()->warning(
                'This will delete ALL keys in the Redis database for {site}.{env}.',
                ['site' => $env->getSite()->getName(), 'env' => $env->getName()]
            );

            if (!$this->io()->confirm('Are you sure you want to continue?')) {
                $this->log()->notice('Operation cancelled.');
                return;
            }
        }

        $redis_command = $connection_info['redis_command'];

        // Execute FLUSHDB command
        $command = sprintf('%s FLUSHDB', $redis_command);

        $this->log()->notice('Flushing Redis database...');

        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new TerminusException(
                'Redis FLUSHDB failed with exit code {code}',
                ['code' => $return_var]
            );
        }

        $this->log()->notice('Redis database flushed successfully.');
    }
}
