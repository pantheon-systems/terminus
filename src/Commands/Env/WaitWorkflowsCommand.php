<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class WaitWorkflowsCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class WaitWorkflowsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Waits for all workflows in a given environment and optionally attempt to do healthcheck on it.
     *
     * @authorize
     *
     * @command env:wait-workflows
     * @aliases env:ww
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option bool $healthcheck Whether to wait for the healthcheck to pass
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Wakes <site>'s <env> environment by pinging it.
     */
    public function waitWorkflows($site_env, $options = [
        'healthcheck' => true,
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        if ($options['healthcheck']) {
            $env->wake();            
        }

        $this->log()->notice('OK');
    }
}
