<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class MergeFromDevCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Merges the changes from the dev environment into a multidev environment
     *
     * @authorized
     *
     * @command multidev:merge-from-dev
     * @aliases env:merge-from-dev
     *
     * @param string $site_env Site & environment to merge changes from dev into, in the form `site-name.env`.
     * @option boolean $updatedb True to update the DB along with this merge
     *
     * @usage terminus multidev:merge-from-dev awesome-site.multidev-env
     *   Merges the code from the dev environment into the multidev-env environment
     * @usage terminus multidev:merge-from-dev awesome-site.multidev-env --updatedb
     *   Merges the code and database from the dev environment into the multidev-env environment
     */
    public function mergeFromDev($site_env, $options = ['updatedb' => false,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->mergeFromDev(['updatedb' => $options['updatedb'],]);
        $workflow->wait();
        if ($workflow->isSuccessful()) {
            $this->log()->notice('Merged the dev environment into {env}.', ['env' => $env->id,]);
        } else {
            throw new TerminusException($workflow->getMessage());
        }
    }
}
