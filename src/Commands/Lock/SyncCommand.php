<?php

namespace Pantheon\Terminus\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SyncCommand
 * @package Pantheon\Terminus\Commands\Lock
 */
class SyncCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Displays HTTP basic authentication status and configuration for the environment.
     *
     * @authorize
     *
     * @command lock:sync
     *
     * @param string $site_env Origin site & environment in the format `site-name.env`
     * @param string $target_env Target environment
     *
     * @usage <site>.<env> <target_env> Synchronizes the lock settings from <site>'s <env> environment to <target_env>.
     */
    public function sync($site_env, $target_env)
    {
        /** @var \Pantheon\Terminus\Models\Site $site */
        /** @var \Pantheon\Terminus\Models\Environment $source_env */
        list($site, $source_env) = $this->getSiteEnv($site_env);

        if ($source_env->id === $target_env) {
            $this->log()->notice('The sync has been skipped because the source and target environments are the same.');
            return;
        }

        $target = $site->getEnvironments()->get($target_env);

        /** @var \Pantheon\Terminus\Models\Lock $source_lock */
        $source_lock = $source_env->getLock();
        $target_lock = $target->getLock();

        if ($source_lock->serialize() == $target_lock->serialize()) {
            $this->log()->notice('The source and target environment locks already match.');
            return;
        }

        if (!$source_lock->isLocked()) {
            $this->processWorkflow($target_lock->disable());
        } else {
            $this->processWorkflow($target_lock->enable([
                'username' => $source_lock->get('username'),
                'password' => $source_lock->get('password'),
            ]));
        }

        $this->log()->notice(
            '{site}.{env} lock has been synced to {target_env}.',
            ['site' => $site->get('name'), 'env' => $source_env->id, 'target_env' => $target->id,]
        );
    }
}
