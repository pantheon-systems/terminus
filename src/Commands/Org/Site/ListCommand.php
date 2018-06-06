<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Friends\RowsOfFieldsInterface;
use Pantheon\Terminus\Friends\RowsOfFieldsTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Site
 */
class ListCommand extends TerminusCommand implements RowsOfFieldsInterface, SiteAwareInterface
{
    use RowsOfFieldsTrait;
    use SiteAwareTrait;

    /**
     * Displays the list of sites associated with an organization.
     *
     * @authorize
     *
     * @command org:site:list
     * @aliases org:sites
     *
     * @field-labels
     *     name: Name
     *     id: ID
     *     plan_name: Plan
     *     framework: Framework
     *     owner: Owner
     *     created: Created
     *     tags: Tags
     *     frozen: Is Frozen?
     * @return RowsOfFields
     *
     * @param string $organization Organization name, label, or ID
     * @option string $tag Tag name to filter
     * @option string $upstream Upstream name to filter
     *
     * @usage <organization> Displays the list of sites associated with <organization>.
     * @usage <organization> --tag=<tag> Displays the list of sites associated with <organization> that have the <tag> tag.
     * @usage <organization> --upstream=<upstream_uuid> Displays the list of sites associated with <organization> on the upstream with id <upstream_uuid>.
     */
    public function listSites($organization, $options = ['tag' => null, 'upstream' => null])
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $this->sites->fetch(['org_id' => $org->id,]);
        if (!is_null($tag = $options['tag'])) {
            $this->sites->filterByTag($tag);
        }
        if (!is_null($upstream = $options['upstream'])) {
            $this->sites->filterByUpstream($upstream);
        }
        return $this->getRowsOfFields(
            $this->sites,
            ['message' => 'This organization has no sites.',]
        );
    }
}
