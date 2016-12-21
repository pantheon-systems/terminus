<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Org\Team
 */
class AddCommand extends TerminusCommand
{
    /**
     * Add a team member to an organization
     *
     * @authorize
     *
     * @command org:team:add
     *
     * @param string $organization The name or UUID of the organization to add a team to
     * @param string $email The email address of the new team member to be added to this organization
     * @param string $role [admin|unprivileged|team_member|developer] The role to assign to this member
     *
     * @usage <organization> <email> <role>
     *   Adds the person with the address <email> to the team of the <organization> organization with the role <role>.
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
