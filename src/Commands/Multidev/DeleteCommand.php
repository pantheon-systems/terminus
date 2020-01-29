<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class DeleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Deletes a Multidev environment.
     *
     * @authorize
     *
     * @command multidev:delete
     * @aliases env:delete
     *
     * @param string $site_env Site & Multidev environment in the format `site-name.env`
     * @option boolean $delete-branch Delete associated Git branch
     *
     * @usage <site>.<multidev> Deletes <site>'s <multidev> Multidev environment.
     * @usage <site>.<multidev> --delete-branch Deletes <site>'s <multidev> Multidev environment and deletes its associated Git branch.
     */
    public function deleteMultidev($site_env, $options = ['delete-branch' => false,])
    {
        list(, $env) = $this->getSiteEnv($site_env);

        if (!$this->confirm('Are you sure you want to delete {env}?', ['env' => $env->getName()])) {
            return;
        }

        $workflow = $env->delete(
            ['delete_branch' => isset($options['delete-branch']) ? $options['delete-branch'] : false,]
        );
        $this->processWorkflow($workflow);
        $this->log()->notice('Deleted the multidev environment {env}.', ['env' => $env->id,]);
    }
}
