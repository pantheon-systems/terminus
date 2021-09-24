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
     * Applies upstream updates to a site development environment.
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
        $site = $this->getSite($site_env);
        $env = $this->getEnv($site_env);

        if (in_array($env->getName(), ['test', 'live'])) {
            throw new TerminusException(
                'Upstream updates cannot be applied to the {env} environment',
                ['env' => $env->getName()]
            );
        }

        $updates = $this->getUpstreamUpdatesLog($env);
        $composerUpdates = $this->getComposerUpdatesLog($env);

        $count = count($updates);
        $composerCount = count($composerUpdates);
        if ($count || $composerCount) {
            $prefix = sprintf("Applying %d upstream update(s)", $count);
            if ($composerCount) {
                $prefix .= " and any composer update(s)";
            }
            $this->log()->notice(
                '{prefix} to the {env} environment of {site_id}...',
                [
                    'prefix' => $prefix,
                    'env' => $env->getName(),
                    'site_id' => $site->getName(),
                ]
            );
            $workflow = $env->applyUpstreamUpdates(
                $options['updatedb'] ?? false,
                $options['accept-upstream'] ?? false
            );
            $this->processWorkflow($workflow);
            $this->log()->notice($workflow->getMessage());
        } else {
            $this->log()->warning('There are no available updates for this site.');
        }
    }
}
