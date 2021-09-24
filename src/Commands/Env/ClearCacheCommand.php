<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ClearCacheCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class ClearCacheCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Clears caches for the environment.
     *
     * @authorize
     *
     * @command env:clear-cache
     * @aliases env:cc
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Clears caches for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusProcessException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function clearCache($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        $this->processWorkflow($env->clearCache());
        $this->log()->notice(
            'Caches cleared on {env}.',
            ['env' => $env->getName()]
        );
    }
}
