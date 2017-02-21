<?php

namespace Pantheon\Terminus\Commands\Org\People;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Org\People
 */
class AddCommand extends TerminusCommand
{
    /**
     * Adds a user to an organization.
     *
     * @authorize
     *
     * @command org:people:add
     * @aliases org:ppl:add
     *
     * @param string $organization Organization name, label, or ID
     * @param string $email Email address
     * @param string $role [admin|unprivileged|team_member|developer] Role
     *
     * @usage <organization> <email> <role> Adds the user with the email, <email>, to <organization> with the <role> role.
     */
    public function add($organization, $email, $role)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $workflow = $org->getUserMemberships()->create($email, $role);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{email} has been added to the {org} organization as a(n) {role}.',
            ['email' => $email, 'org' => $org->getName(), 'role' => $role,]
        );
    }
}
