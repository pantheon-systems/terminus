<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Team
 */
class ListCommand extends TerminusCommand
{
    /**
     * List the team members of a given organization
     *
     * @authorize
     *
     * @command org:team:list
     * @aliases org:team
     *
     * @field-labels
     *   id: ID
     *   first_name: First Name
     *   last_name: Last Name
     *   email: Email
     *   role: Role
     *
     * @param string $organization The name or UUID of the organization to list the team members of
     * @return RowsOfFields
     *
     * @usage terminus org:team:list <organization>
     *   Displays a list of the team members belonging to the <organization> organization
     */
    public function listTeam($organization)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $members = array_map(
            function ($member) {
                return $member->serialize();
            },
            $org->getUserMemberships()->fetch()->all()
        );
        if (empty($members)) {
            $this->log()->notice('{org} has no team members.', ['org' => $org->get('profile')->name,]);
        }
        return new RowsOfFields($members);
    }
}
