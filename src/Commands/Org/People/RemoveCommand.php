<?php

namespace Pantheon\Terminus\Commands\Org\People;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Org\People
 */
class RemoveCommand extends TerminusCommand
{
    /**
     * Removes a user from an organization.
     *
     * @authorize
     *
     * @command org:people:remove
     * @aliases org:people:rm org:ppl:remove org:ppl:rm
     *
     * @param string $organization Organization name, label, or ID
     * @param string $member User UUID, email address, or full name
     *
     * @usage <organization> <user> Removes the user, <user>, from <organization>.
     */
    public function remove($organization, $member)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $membership = $org->getUserMemberships()->fetch()->get($member);
        $workflow = $membership->delete();
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{member} has been removed from the {org} organization.',
            ['member' => $membership->getUser()->getName(), 'org' => $org->getName(),]
        );
    }
}
