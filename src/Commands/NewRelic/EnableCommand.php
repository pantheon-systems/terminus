<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enable New Relic on a site
     *
     * @authorized
     *
     * @command new-relic:enable
     *
     * @param string $site_id Name of the site to enable New Relic on
     *
     * @usage terminus new-relic:enable my-site
     *   Enables New Relic for the site named 'my-site'.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getNewRelic()->enable();
        $this->log()->notice('New Relic enabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
