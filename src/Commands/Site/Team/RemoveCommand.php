<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Site\Team
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Remove a team member from the site's team
     *
     * @authorize
     *
     * @command site:team:remove
     * @aliases site:team:rm
     *
     * @param string $site_id Site name or UUID to remove member from
     * @param string $member Email, UUID, or full name of the member to remove
     *
     * @usage terminus site:team:remove <site> <user>
     *   Remove <user> from the site <site>
     */
    public function remove($site_id, $member)
    {
        $workflow = $this->getSite($site_id)->getUserMemberships()->get($member)->delete();
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
