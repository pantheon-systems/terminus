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
     * Adds a user to an organization.
     *
     * @authorize
     *
     * @command org:team:add
     *
     * @param string $organization Organization name or ID
     * @param string $email Email address
     * @param string $role [admin|unprivileged|team_member|developer] Role
     *
     * @usage terminus org:team:add <organization> <email> <role>
     *     Adds the user with the email, <email>, to <organization> with the <role> role.
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
