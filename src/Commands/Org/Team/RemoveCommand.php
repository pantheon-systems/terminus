<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Org\Team
 */
class RemoveCommand extends TerminusCommand
{
    /**
     * Remove a team member from an organization
     *
     * @authorize
     *
     * @command org:team:remove
     *
     * @param string $organization The name or UUID of the organization to remove a team member from
     * @param string $member The UUID, email address, or full name of the team member to remove from this organization
     *
     * @usage <organization> <member>
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
