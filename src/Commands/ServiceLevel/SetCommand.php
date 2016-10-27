<?php

namespace Pantheon\Terminus\Commands\ServiceLevel;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Set a site's service level.
     *
     * @command service-level:set
     *
     * @param string $site_id The name of the site to set the service level for.
     * @param string $level The level to set the site to. Options are free, basic, pro, and business.
     */
    public function set($site_id, $level)
    {
        $site = $this->getSite($site_id);
        $workflow = $site->updateServiceLevel($level);
        $this->log()->notice('Setting plan of "{site_id}" to "{level}".', compact('site_id', 'level'));
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
