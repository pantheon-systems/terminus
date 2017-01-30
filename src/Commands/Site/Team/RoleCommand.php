<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class RoleCommand
 * @package Pantheon\Terminus\Commands\Site\Team
 */
class RoleCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Updates a user's role on a site's team.
     *
     * @authorize
     *
     * @command site:team:role
     *
     * @param string $site_id Site name
     * @param string $member Email, UUID, or full name
     * @param string $role [developer|team_member] Role
     *
     * @usage <site> <user> <role> Updates <user> to be a <role> on <site>'s team.
     */
    public function role($site_id, $member, $role)
    {
        $site = $this->getSite($site_id);
        if (!(boolean)$site->getFeature('change_management')) {
            throw new TerminusException('This site does not have its change-management option enabled.');
        }
        $workflow = $site->getUserMemberships()->get($member)->setRole($role);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
