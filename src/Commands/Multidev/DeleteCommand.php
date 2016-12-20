<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class DeleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Delete a multidev environment
     *
     * @authorize
     *
     * @command multidev:delete
     * @aliases env:delete
     *
     * @param string $site_env Site & environment to delete, in the form `site-name.env`
     * @option boolean $delete-branch Set to delete the branch of the same name
     *
     * @usage terminus multidev:delete <site>.<multidev>
     *   Deletes the <multidev> environment from <site>
     * @usage terminus multidev:delete awesome-site.multidev-env --delete-branch
     *   Deletes the <multidev> environment from <site> and deletes its branch from the repository
     */
    public function deleteMultidev($site_env, $options = ['delete-branch' => false,])
    {
        list(, $env) = $this->getSiteEnv($site_env);

        if (!$this->confirm('Are you sure you want to delete {env}?', ['env' => $env->getName()])) {
            return;
        }

        $workflow = $env->delete(['delete_branch' => $options['delete-branch'],]);
        $workflow->wait();
        if ($workflow->isSuccessful()) {
            $this->log()->notice('Deleted the multidev environment {env}.', ['env' => $env->id,]);
        } else {
            throw new TerminusException($workflow->getMessage());
        }
    }
}
