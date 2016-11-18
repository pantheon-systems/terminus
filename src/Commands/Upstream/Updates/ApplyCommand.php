<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ApplyCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ApplyCommand extends UpdatesCommand
{

    /**
     * Apply the available upstream updates to the given site's environment
     *
     * @authorize
     *
     * @command upstream:updates:apply
     *
     * @param string $site_env Site & environment to which to apply updates
     * @option boolean $updatedb Run update.php after updating (Drupal only)
     * @option boolean $accept-upstream Attempt to automatically resolve conflicts in favor of the upstream
     *
     * @throws TerminusException
     *
     * @usage terminus upstream:updates:apply <site>.<env>
     *   Applies the available updates to the <env> environment of <site>
     * @usage terminus upstream:updates:apply <site>.<env> --updatedb
     *   Applies the available updates to the <env> environment of <site> and run update.php when finished
     * @usage terminus upstream:updates:apply <site>.<env> --accept-upstream
     *   Applies the available updates to the <env> environment of <site>, automatically resolving conflicts
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

        $updates = $this->getUpstreamUpdatesLog($site);
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

            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        } else {
            $this->log()->warning('There are no available updates for this site.');
        }
    }
}
