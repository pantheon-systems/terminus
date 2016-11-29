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
     * Change a team member's role
     *
     * @authorize
     *
     * @command site:team:role
     *
     * @param string $site_id Site name or UUID to change roles on
     * @param string $member Email of the member to change the role of
     * @param string $role [unprivileged|admin|developer|team_member] Role to designate the member as
     *
     * @usage terminus site:team:role <site> <user> <role>
     *   Change <user> to have the role of <role> on the site <site>
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
