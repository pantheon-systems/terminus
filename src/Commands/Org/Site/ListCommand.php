<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Site
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List the sites belonging to a given organization
     *
     * @authorize
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
     * @return RowsOfFields
     *
     * @param string $organization The name or UUID of the organization to list the sites of
     * @option string $tag A tag by which to filter the list of sites
     *
     * @usage <organization>
     *   Displays a list of the sites belonging to the <organization> organization
     * @usage <organization> --tag=<tag>
     *   Displays a list of the sites belonging to the <organization> organization, filtered by the tag <tag>
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
