<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ClearCacheCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class ClearCacheCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Clears the cache on an environment
     *
     * @authorize
     *
     * @command env:clear-cache
     * @aliases env:cc
     *
     * @param string $site_env The site and environment to clear the cache of
     *
     * @usage terminus env:clear-cache <site>.<env>
     *    Clears the cache of the <env> environment of <site>
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
