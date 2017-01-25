<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class MergeFromDevCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class MergeFromDevCommand extends TerminusCommand implements SiteAwareInterface
{
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
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('Merged the dev environment into {env}.', ['env' => $env->id,]);
    }
}
