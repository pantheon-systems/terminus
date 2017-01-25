<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disables New Relic for a site.
     *
     * @authorize
     *
     * @command new-relic:disable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Disables New Relic for <site>.
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getNewRelic()->disable();
        $this->log()->notice('New Relic disabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
