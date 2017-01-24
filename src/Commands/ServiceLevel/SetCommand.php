<?php

namespace Pantheon\Terminus\Commands\ServiceLevel;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\ServiceLevel
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Upgrades or downgrades a site's service level.
     *
     * @authorize
     *
     * @command service-level:set
     *
     * @param string $site_id Site name
     * @param string $level [free|basic|pro|business] Service level
     *
     * @usage <site> <service_level> Updates <site>'s service level to <service_level>.
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
