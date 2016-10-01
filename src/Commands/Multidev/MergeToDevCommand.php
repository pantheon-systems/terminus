<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class MergeToDevCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Merges the changes in a multidev environment into the dev environment
     *
     * @authorized
     *
     * @name multidev:merge-to-dev
     * @alias env:merge-to-dev
     *
     * @param string $site_env Site & environment to merge into dev, in the form `site-name.env`.
     * @option boolean $updatedb True to update the DB along with this merge
     *
     * @usage terminus multidev:merge-to-dev awesome-site.multidev-env
     *   Merges the code from the multidev-env environment into the dev environment
     * @usage terminus multidev:merge-to-dev awesome-site.multidev-env --updatedb
     *   Merges the code and database from the multidev-env environment into the dev environment
     */
    public function mergeToDev($site_env, $options = ['updatedb' => false,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $site->environments->get('dev')->mergeToDev(
            ['from_environment' => $env->id, 'updatedb' => $options['updatedb'],]
        );
        $workflow->wait();
        if ($workflow->isSuccessful()) {
            $this->log()->notice('Merged the {env} environment into dev.', ['env' => $env->id,]);
        } else {
            throw new TerminusException($workflow->getMessage());
        }
    }
}
