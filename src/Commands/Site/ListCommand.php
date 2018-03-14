<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class ListCommand extends SiteCommand
{
    /**
     * Displays the list of sites accessible to the currently logged-in user.
     *
     * @authorize
     *
     * @command site:list
     * @aliases sites
     *
     * @field-labels
     *     name: Name
     *     id: ID
     *     service_level: Service Level
     *     framework: Framework
     *     owner: Owner
     *     created: Created
     *     memberships: Memberships
     *     frozen: Is Frozen?
     *     last_frozen_at: Date frozen
     * @default-fields name,id,service_level,framework,owner,created,memberships,frozen
     * @return RowsOfFields
     *
     * @option name Name filter
     * @option org Organization filter; "all" or an organization's name, label, or ID
     * @option owner Owner filter; "me" or user UUID
     * @option team Team-only filter
     *
     * @usage Displays the list of all sites accessible to the currently logged-in user.
     * @usage --name=<regex> Displays a list of accessible sites with a name that matches <regex>.
     * @usage --org=<org> Displays a list of accessible sites associated with the <org> organization.
     * @usage --org=all Displays a list of accessible sites associated with any organization of which the currently logged-in is a member.
     * @usage --owner=<user> Displays the list of accessible sites owned by the user with UUID <user>.
     * @usage --owner=me Displays the list of sites owned by the currently logged-in user.
     * @usage --team Displays the list of sites of which the currently logged-in user is a member of the team.
     */
    public function index($options = ['name' => null, 'org' => 'all', 'owner' => null, 'team' => false,])
    {
        $user = $this->session()->getUser();
        $this->sites()->fetch(
            [
                'org_id' => (isset($options['org']) && ($options['org'] !== 'all')) ? $user->getOrganizationMemberships()->get($options['org'])->getOrganization()->id : null,
                'team_only' => isset($options['team']) ? $options['team'] : false,
            ]
        );

        if (isset($options['name']) && !is_null($name = $options['name'])) {
            $this->sites->filterByName($name);
        }
        if (isset($options['owner']) && !is_null($owner = $options['owner'])) {
            if ($owner == 'me') {
                $owner = $user->id;
            }
            $this->sites->filterByOwner($owner);
        }

        $sites = $this->sites->serialize();

        if (empty($sites)) {
            $this->log()->notice('You have no sites.');
        }

        return new RowsOfFields($sites);
    }
}
