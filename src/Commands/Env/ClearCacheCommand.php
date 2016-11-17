<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ClearCacheCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Clears the cache on an environment
     *
     * @authorized
     *
     * @command env:clear-cache
     * @aliases env:cc
     *
     * @param string $site_env The site and environment to clear the cache of
     */
    public function clearCache($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->clearCache();
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('Caches cleared on {site}.{env}.', ['site' => $site->get('name'), 'env' => $env->id,]);
    }
}
