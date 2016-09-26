<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Terminus\Exceptions\TerminusException;

class UpdatesApplyCommand extends UpstreamCommand
{

    /**
     * Applies the available upstream updates to the given site.
     *
     * @authorized
     *
     * @name upstream:updates:apply
     *
     * @param string $site_env_id Name of the environment to retrieve
     *
     * @return void
     *
     * @throws TerminusException
     *
     * @usage terminus upstream:updates:apply <site-name>.<env>
     *   Lists the available updates for the site called <site-name> and the environment <env>
     */
    public function applyUpstreamUpdates(
        $site_env_id,
        $options = ['updatedb' => true, 'accept-upstream' => true,]
    ) {

        list($site, $env) = $this->getSiteEnv($site_env_id, 'dev');

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
                $options['updatedb'],
                $options['accept-upstream']
            );

            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        } else {
            $this->log()->warning("There are no available updates for this site.");
        }
    }
}
