<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

class RemoveCommand extends TerminusCommand
{
    /**
     * Removes a team member to an organization
     *
     * @authorized
     *
     * @command org:team:remove
     *
     * @param string $organization The name or UUID of the organization to remove a team member from
     * @param string $member The UUID, email address, or full name of the team member to remove from this organization
     *
     * @usage terminus org:team:remove <organization> <member>
     *   Removes the team member identified by <member> from the <organization> organization
     */
    public function remove($organization, $member)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $membership = $org->getUserMemberships()->fetch()->get($member);
        $workflow = $membership->delete();
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{member} has been removed from the {org} organization.',
            ['member' => $membership->getUser()->get('profile')->full_name, 'org' => $org->get('profile')->name,]
        );
    }
}
