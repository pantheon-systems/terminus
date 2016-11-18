<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RoleCommand
 * @package Pantheon\Terminus\Commands\Org\Team
 */
class RoleCommand extends TerminusCommand
{
    /**
     * Change an organizational team member's role
     *
     * @authorize
     *
     * @command org:team:role
     *
     * @param string $organization The name or UUID of the organization to of which the user is a member
     * @param string $member The UUID, email address, or full name of the user to change the role of
     * @param string $role [unprivileged|admin|team_member|developer] The role to assign to this member
     *
     * @usage terminus org:team:role <organization> <member> <role>
     *   Changes the role of the team member identified by <member> from the <organization> organization to <role>.
     */
    public function role($organization, $member, $role)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $membership = $org->getUserMemberships()->fetch()->get($member);
        $workflow = $membership->setRole($role);
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            "{member}'s role has been changed to {role} in the {org} organization.",
            [
                'member' => $membership->getUser()->get('profile')->full_name,
                'role' => $role,
                'org' => $org->get('profile')->name,
            ]
        );
    }
}
