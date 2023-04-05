<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RotateRandomSeedCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class RotateRandomSeedCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Rotate random seed for the environment.
     *
     * @authorize
     *
     * @command env:rotate-random-seed
     * @aliases env:rotate-seed
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Rotate random seed for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusProcessException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function rotateRandseed($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSite($site_env);
        $env = $this->getEnv($site_env);

        if (!$this->confirm(
            'Are you sure you want to log out all active users and invalidate all unused one-time login links for the {env} environment of {site}?',
            ['site' => $site->getName(), 'env' => $env->getName()]
        )) {
            return;
        }

        $this->processWorkflow($env->rotateRandseed());
        $this->log()->notice(
            'Random seed rotated on {env}.',
            ['env' => $env->getName()]
        );
    }
}
