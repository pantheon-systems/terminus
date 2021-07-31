<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ClearCacheCommand
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
     */
    public function clearCache($site_env)
    {
        [$site, $env] = explode('.', $site_env);
        if (empty($site) || empty($env)) {
            throw new TerminusNotFoundException(
                'The Site and environment must take the form of {site}.{env} followed by the domain name you are adding'
            );
        }

        $env = $this->sites()->get($site)->getEnvironments()->get($env) ?? null;
        if (!$env instanceof Environment) {
            throw new TerminusNotFoundException(
                'Site/env not found {env}',
                ['env' => $env]
            );
        }
        if ($env->getSite()->isFrozen()) {
            throw new TerminusProcessException(
                "This operation does not work on a frozen site: {env}.",
                ["env" => $env]
            );
        }
        $this->processWorkflow($env->clearCache());
        $this->log()->notice(
            'Caches cleared on {env}.',
            [ 'env' => $env]
        );
    }
}
