<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ConnectCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class ConnectCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Opens an interactive Redis CLI connection to the environment.
     *
     * @authorize
     * @interact
     *
     * @command redis:connect
     * @aliases rc
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Opens an interactive Redis CLI connection to <site>'s <env> environment.
     *
     * @throws TerminusException
     */
    public function connect($site_env)
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

        $this->log()->notice('Connecting to Redis...');

        // Execute the Redis command in passthru mode to allow interactive use
        passthru($redis_command, $return_var);

        if ($return_var !== 0) {
            throw new TerminusException(
                'Redis connection failed with exit code {code}',
                ['code' => $return_var]
            );
        }
    }
}
