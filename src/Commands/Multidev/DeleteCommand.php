<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class DeleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Deletes a multidev environment
     *
     * @authorized
     *
     * @name multidev:delete
     *
     * @param string $site_env Site & environment to delete, in the form `site-name.env`.
     * @option boolean $delete-branch Used to delete the branch of the same name
     *
     * @usage terminus multidev:delete awesome-site.multidev-env
     *   Deletes the multidev-env from awesome-site
     * @usage terminus multidev:delete awesome-site.multidev-env --delete-branch
     *   Deletes the multidev-env from awesome-site and deletes the branch of the same name from the remote repository
     */
    public function deleteMultidev($site_env, $options = ['delete-branch' => false,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->delete(['delete_branch' => $options['delete-branch'],]);
        $workflow->wait();
        if ($workflow->isSuccessful()) {
            $this->log()->notice('Deleted the multidev environment {env}.', ['env' => $env->id,]);
        } else {
            throw new TerminusException($workflow->getMessage());
        }
    }
}
