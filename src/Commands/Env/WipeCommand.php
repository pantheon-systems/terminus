<?php

namespace Pantheon\Terminus\Commands\Env;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class WipeCommand
 * Testing class for Pantheon\Terminus\Commands\Env\WipeCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class WipeCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Deletes all files and database content in the environment.
     *
     * @authorize
     *
     * @command env:wipe
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Deletes all database/files on <site>'s <env> environment.
     */
    public function wipe($site_env)
    {
        list($site, $env) = $this->getUnfrozenSiteEnv($site_env);

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you want to wipe {env} on {site}?', $tr)) {
            return;
        }

        $workflow = $env->wipe();
        $this->log()->notice(
            'Wiping the "{env}" environment of "{site}"',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
