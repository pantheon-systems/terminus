<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class ClearCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Clearing the Redis caching for the a site.
     *
     * @authorized
     *
     * @command redis:clear
     *
     * @param string $site_env Name of the site and environment to clear Redis for
     *
     * @throws \Terminus\Exceptions\TerminusException
     * @usage terminus redis:clear my-site
     *   Clear redis caching for the site named 'my-site'.
     */
    public function clearRedis($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $connection_info = $env->connectionInfo();
        if (empty($connection_info['redis_host'])) {
            throw new TerminusException('Redis cache is not enabled for {site_id}.', ['site_id' => $site->get('name')]);
        }

        $workflow = $site->redis->clear($env);
        $this->log()->notice('Clearing Redis on {env}.', ['env' => $env->id]);
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
