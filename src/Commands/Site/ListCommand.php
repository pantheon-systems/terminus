<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class ListCommand extends SiteCommand
{
    /**
     * List the sites accessible by the logged-in user
     *
     * @authorize
     *
     * @command site:list
     * @alias sites
     *
     * @field-labels
     *   name: Name
     *   id: ID
     *   service_level: Service Level
     *   framework: Framework
     *   owner: Owner
     *   created: Created
     *   memberships: Memberships
     * @return RowsOfFields
     *
     * @option team Filter for sites you are a team member of
     * @option owner Filter for sites a specific user owns. Use "me" for your own user
     * @option org Filter sites you can access via the organization. Use "all" to get all.
     * @option name Filter sites you can access via name.
     *
     * @usage site:list
     *   * Responds with list of every site you can access
     *   * Responds with "You have no sites." if you have no sites
     * @usage site:list --team
     *   * Responds with a list of sites you are a team member of
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage site:list --owner=<user>
     *   * Responds with a list of sites owned by the user identified by <user>
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage --owner=me
     *   * Responds with a list of sites the logged-in user owns
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage --org=<org>
     *   * Responds with a list of sites associated with the <org> organization
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage --org=all
     *   * Responds with a list of sites belonging to organization you are a member of
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage --name=<regex>
     *   * Responds with a list of sites you have access to by name with a name matching the provided <regex>
     *   * Responds with a notice stating no sites match criteria if none exist
     */
    public function index($options = ['team' => false, 'owner' => null, 'org' => null, 'name' => null,])
    {
        $this->sites()->fetch(
            [
                'org_id' => isset($options['org']) ? $options['org'] : null,
                'team_only' => isset($options['team']) ? $options['team'] : false,
            ]
        );

        if (isset($options['name']) && !is_null($name = $options['name'])) {
            $this->sites->filterByName($name);
        }
        if (isset($options['owner']) && !is_null($owner = $options['owner'])) {
            if ($owner == 'me') {
                $owner = $this->session()->getUser()->id;
            }
            $this->sites->filterByOwner($owner);
        }

        $sites = array_map(
            function ($site) {
                return $site->serialize();
            },
            $this->sites->all()
        );

        if (empty($sites)) {
            $this->log()->notice('You have no sites.');
        }

        return new RowsOfFields($sites);
    }
}
