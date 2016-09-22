<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Terminus\Collections\Sites;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Site;

class UpdatesApplyCommand extends UpstreamCommand
{

    /**
     * Applies the available upstream updates to the given site.
     *
     * @authorized
     *
     * @name upstream :updates:apply
     *
     * @param string $environment Name of the environment to retrieve
     *
     * @return void
     *
     * @throws TerminusException
     *
     * @usage terminus upstream:updates:apply <site-name>.<env>
     *   Lists the available updates for the site called <site-name> and the environment <env>
     */
    public function applyUpstreamUpdates(
        $environment,
        $options = ['updatedb' => true, 'accept-upstream' => true]
    ) {
        $parts = explode('.', $environment);
        $site_id = $parts[0];
        $env = !empty($parts[1]) ? $parts[1] : 'dev';
        if (in_array($env, ['test', 'live',])) {
            throw new TerminusException(
                'Upstream updates cannot be applied to the {env} environment',
                compact('env')
            );
        }

        $updates = $this->getUpstreamUpdatesLog($site_id);
        $count = count($updates);
        if ($count) {
            $this->log()->notice(
                'Applying {count} upstream update(s) to the {env} environment of {site_id}...',
                compact('count', 'env', 'site_id')
            );

            $site = $this->getSite($site_id);
            $environment = $site->environments->get($env);
            $workflow = $environment->applyUpstreamUpdates(
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
