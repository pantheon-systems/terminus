<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disable New Relic for the a site
     *
     * @authorized
     *
     * @command new-relic:disable
     *
     * @param string $site_id Name of the site to disable New Relic on
     *
     * @usage terminus new-relic:disable my-site
     *   Disables New Relic for the site named 'my-site'.
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
