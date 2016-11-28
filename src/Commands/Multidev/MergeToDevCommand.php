<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class MergeToDevCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class MergeToDevCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Merge the changes in a multidev environment into the dev environment
     *
     * @authorize
     *
     * @command multidev:merge-to-dev
     * @aliases env:merge-to-dev
     *
     * @param string $site_env Site & environment to merge into dev, in the form `site-name.env`.
     * @option boolean $updatedb True to update the database along with this merge
     *
     * @usage terminus multidev:merge-to-dev <site>.<env>
     *   Merges the code from the multidev <env> environment of <site> into its dev environment
     * @usage terminus multidev:merge-to-dev awesome-site.multidev-env --updatedb
     *   Merges the code and database from the multidev <env> environment of <site> into its dev environment
     */
    public function mergeToDev($site_env, $options = ['updatedb' => false,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $site->getEnvironments()->get('dev')->mergeToDev(
            ['from_environment' => $env->id, 'updatedb' => $options['updatedb'],]
        );
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('Merged the {env} environment into dev.', ['env' => $env->id,]);
    }
}
