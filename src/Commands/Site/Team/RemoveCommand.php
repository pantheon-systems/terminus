<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Remove a team member from the site's team.
     *
     * @command site:team:remove
     *
     * @param string $site_id Site name to remove member from.
     * @param string $member Email of the member to remove.
     *
     * @usage terminus site:team:remove my-site admin@agency.com
     *   Remove `admin@agency.com` from the site `my-site`.
     */
    public function remove($site_id, $member)
    {
        $site = $this->getSite($site_id);
        $team = $site->user_memberships;
        $user = $team->get($member);
        $workflow = $user->delete();
        return $workflow->getMessage();
    }
}
