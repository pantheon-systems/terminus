<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Site\Team
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Add a team member to a site
     *
     * @authorize
     *
     * @command site:team:add
     *
     * @param string $site_id Site name or UUID to add a team member to
     * @param string $member Email of the user to add; they will receive an invitation
     * @param string $role [unprivileged|admin|team_member|developer] Role to designate the new member as
     *
     * @usage <site> <user>
     *   Add <user> in the team_member role to the site <site>
     * @usage <site> <user> <role>
     *   Add <user> in the <role> role to the site <site>
     */
    public function add($site_id, $member, $role)
    {
        $site = $this->getSite($site_id);
        $team = $site->getUserMemberships();

        if (!(boolean)$site->getFeature('change_management')) {
            $role = 'team_member';
            $this->log()->warning(
                'Site does not have change management enabled, defaulting to user role {role}.',
                $options
            );
        }
        $workflow = $team->create($member, $role);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
