<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

class RoleCommand extends TerminusCommand
{
    /**
     * Changes an organizational team member's role
     *
     * @authorized
     *
     * @command org:team:role
     *
     * @param string $organization The name or UUID of the organization to of which the user is a member
     * @param string $member The UUID, email address, or full name of the user to change the role of
     * @param string $role The role to assign to this member. Options are unprivileged, admin, team_member, and developer.
     *
     * @usage terminus org:team:role <organization> <member>
     *   Roles the team member identified by <member> from the <organization> organization
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
