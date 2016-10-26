<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Lists the organizations of which the current user is a member
     *
     * @authorized
     *
     * @command org:site:list
     * @aliases org:sites
     *
     * @field-labels
     *   name: Name
     *   id: ID
     *   service_level: Service Level
     *   framework: Framework
     *   owner: Owner
     *   created: Created
     *   tags: Tags
     *
     * @param string $organization The name or UUID of the organization to list the sites of
     * @option string $tag A tag by which to filter the list of sites
     * @return RowsOfFields
     *
     * @usage terminus org:site:list <organization>
     *   Displays a list of the sites belonging to the <organization> organization
     */
    public function listSites($organization, $options = ['tag' => null,])
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $this->sites->fetch(['org_id' => $org->id,]);
        if (!is_null($tag = $options['tag'])) {
            $this->sites->filterByTag($tag);
        }
        $sites = array_map(
            function ($site) {
                return $site->serialize();
            },
            $this->sites->all()
        );

        if (empty($sites)) {
            $this->log()->notice('This organization has no sites.');
        }

        return new RowsOfFields($sites);
    }
}
