<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disable Redis caching for the a site.
     *
     * @authorized
     *
     * @command redis:disable
     *
     * @param string $site_id Name of the site to disable Redis for
     *
     * @usage terminus redis:disable my-site
     *   Disable redis caching for the site named 'my-site'.
     */
    public function disableRedis($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getRedis()->disable();
        $this->log()->notice('Redis disabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
