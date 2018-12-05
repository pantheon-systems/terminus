<?php

namespace Pantheon\Terminus\Commands\Multidev;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class MergeFromDevCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class MergeFromDevCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Merges code commits from the Dev environment into a Multidev environment.
     *
     * @authorize
     *
     * @command multidev:merge-from-dev
     * @aliases env:merge-from-dev
     *
     * @param string $site_env Site & Multidev environment in the form `site-name.env`
     * @option boolean $updatedb Run update.php afterwards
     *
     * @usage <site>.<multidev> Merges code commits from <site>'s Dev environment into the <multidev> environment.
     * @usage <site>.<multidev> --updatedb Merges code commits from <site>'s Dev environment into the <multidev> environment and runs update.php afterwards.
     */
    public function mergeFromDev($site_env, $options = ['updatedb' => false,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->mergeFromDev(['updatedb' => $options['updatedb'],]);
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice('Merged the dev environment into {env}.', ['env' => $env->id,]);
    }
}
