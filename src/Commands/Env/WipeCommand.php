<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class WipeCommand.
 *
 * Testing class for Pantheon\Terminus\Commands\Env\WipeCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class WipeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Deletes all files and database content in the environment.
     *
     * @authorize
     *
     * @command env:wipe
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> Deletes all database/files on <site>'s <env> environment.
     */
    public function wipe($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if (
            !$this->confirm(
                'Are you sure you want to wipe {env} on {site}?',
                ['site' => $site->getName(), 'env' => $env->getName()]
            )
        ) {
            return;
        }

        $workflow = $env->wipe();
        $this->log()->notice(
            'Wiping the "{env}" environment of "{site}"',
            ['site' => $site->getName(), 'env' => $env->getName()]
        );
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
