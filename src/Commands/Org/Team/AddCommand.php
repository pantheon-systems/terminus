<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

class AddCommand extends TerminusCommand
{
    /**
     * Adds a team member to an organization
     *
     * @authorized
     *
     * @command org:team:add
     *
     * @param string $organization The name or UUID of the organization to add a team to
     * @param string $email The email address of the new team member to be added to this organization
     * @param string $role The role to assign to this member. Options are unprivileged, admin, team_member, and developer.
     *
     * @usage terminus org:team:add <organization> <email>
     *   Adds the person with the address <email> to the team of the <organization> organization
     */
    public function add($organization, $email, $role)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $workflow = $org->getUserMemberships()->create($email, $role);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{email} has been added to the {org} organization as a(n) {role}.',
            ['email' => $email, 'org' => $org->get('profile')->name, 'role' => $role,]
        );
    }
}
