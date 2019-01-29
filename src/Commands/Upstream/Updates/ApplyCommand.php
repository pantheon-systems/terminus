<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ApplyCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ApplyCommand extends UpdatesCommand
{
    use WorkflowProcessingTrait;

    /**
     * Applies upstream updates to a site's development environment.
     *
     * @authorize
     *
     * @command upstream:updates:apply
     *
     * @param string $site_env Site & development environment
     * @option boolean $updatedb Run update.php after update (Drupal only)
     * @option boolean $accept-upstream Attempt to automatically resolve conflicts in favor of the upstream
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Applies upstream updates to <site>'s <env> environment.
     * @usage <site>.<env> --updatedb Applies upstream updates to <site>'s <env> environment and runs update.php after update.
     * @usage <site>.<env> --accept-upstream Applies upstream updates to <site>'s <env> environment and attempts to automatically resolve conflicts in favor of the upstream.
     */
    public function applyUpstreamUpdates($site_env, $options = ['updatedb' => false, 'accept-upstream' => false,])
    {
        list($site, $env) = $this->getSiteEnv($site_env, 'dev');

        if (in_array($env->id, ['test', 'live',])) {
            throw new TerminusException(
                'Upstream updates cannot be applied to the {env} environment',
                ['env' => $env->id,]
            );
        }

        $updates = $this->getUpstreamUpdatesLog($env);
        $count = count($updates);
        if ($count) {
            $this->log()->notice(
                'Applying {count} upstream update(s) to the {env} environment of {site_id}...',
                ['count' => $count, 'env' => $env->id, 'site_id' => $site->get('name'),]
            );

            $workflow = $env->applyUpstreamUpdates(
                isset($options['updatedb']) ? $options['updatedb'] : false,
                isset($options['accept-upstream']) ? $options['accept-upstream'] : false
            );

            $this->processWorkflow($workflow);
            $this->log()->notice($workflow->getMessage());
        } else {
            $this->log()->warning('There are no available updates for this site.');
        }
    }
}
