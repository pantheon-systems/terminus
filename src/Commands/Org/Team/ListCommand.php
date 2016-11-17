<?php

namespace Pantheon\Terminus\Commands\Org\Team;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

class ListCommand extends TerminusCommand
{
    /**
     * Lists the organizations of which the current user is a member
     *
     * @authorized
     *
     * @command org:team:list
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
