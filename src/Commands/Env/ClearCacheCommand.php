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
     * @param array $options
     * @option bool $framework_cache [true|false] Clear the CMS cache
     * @option bool $varnish_cache [true|false] Clear the edge cache
     *
     * @usage <site>.<env> Clears caches for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusProcessException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function clearCache($site_env, $options = ['framework_cache' => true, 'varnish_cache' => false,])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        $this->processWorkflow($env->clearCache($options));
        $this->log()->notice(
            'Caches cleared on {env}.',
            ['env' => $env->getName()]
        );
    }
}
