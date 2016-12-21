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
     * Merge the changes from the dev environment into a multidev environment
     *
     * @authorize
     *
     * @command multidev:merge-from-dev
     * @aliases env:merge-from-dev
     *
     * @param string $site_env Site & environment to merge changes from dev into, in the form `site-name.env`.
     * @option boolean $updatedb True to update the DB along with this merge
     *
     * @usage <site>.<env>
     *   Merges the code from the dev environment of <site> into its multidev <env> environment
     * @usage <site>.<env> --updatedb
     *   Merges the code and database from the dev environment of <site> into its multidev <env> environment
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
