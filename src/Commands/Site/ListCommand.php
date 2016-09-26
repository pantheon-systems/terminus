<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Collections\Sites;

class ListCommand extends TerminusCommand
{
    /**
     * Looks up a site name
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
     * @option team Filter for sites you are a team member of
     * @option owner Filter for sites a specific user owns. Use "me" for your own user
     * @option org Filter sites you can access via the organization. Use "all" to get all.
     * @option name Filter sites you can acces via name.
     * @usage terminus site:list
     *   * Responds with list of every site you can access
     *   * Responds with "You have no sites." if you have no sites
     * @usage terminus site:list --team
     *   * Responds with a list of sites you are a team member of
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage terminus site:list --owner=<uuid>
     *   * Responds with a list of sites owned by the user with the given UUID
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage terminus site:list --owner=me
     *   * Responds with a list of sites the logged-in user owns
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage terminus site:list --org=<id|name>
     *   * Responds with a list of sites inside the organization(s) indicated
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage terminus site:list --org=all
     *   * Responds with a list of sites belonging to organization you are a member of
     *   * Responds with a notice stating no sites match criteria if none exist
     * @usage terminus site:list --name=<regex>
     *   * Responds with a list of sites you have access to by name
     *   * Responds with a notice stating no sites match criteria if none exist
     * @return RowsOfFields
     */
    public function list($options = ['team' => false, 'owner' => null, 'org' => null, 'name' => null])
    {
        $sites = new Sites();
        $sites->fetch(
            [
                'org_id' => $options['org'],
                'team_only' => $options['team']
            ]
        );

        if (!is_null($name = $options['name'])) {
            $sites->filterByName($name);
        }
        if (!is_null($owner = $options['owner'])) {
            if ($owner == 'me') {
                $owner = $this->session()->getUser()->id;
            }
            $sites->filterByOwner($owner);
        }

        $all_sites = array_map(
            function ($site) {

                $memberships = array_map(
                    function($membership) {
                        $org_data = $membership->serialize();
                        return "{$org_data['org_id']}: {$org_data['org_name']}";
                    },
                    $site->org_memberships->all()
                );


                $site_data = $site->info();
                return [
                    'name'          => $site_data['name'],
                    'id'            => $site_data['id'],
                    'service_level' => $site_data['service_level'],
                    'framework'     => $site_data['framework'],
                    'owner'         => $site_data['owner'],
                    'created'       => $site_data['created'],
                    'memberships'   => implode(',', $memberships),
                ];
            },
            $sites->all()
        );

        return new RowsOfFields($all_sites);
    }
}