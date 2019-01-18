<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Friends\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Site
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

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
     *
     * @usage <organization> Displays the list of sites associated with <organization>.
     * @usage <organization> --tag=<tag> Displays the list of sites associated with <organization> that have the <tag> tag.
     */
    public function listSites($organization, $options = ['tag' => null,])
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $this->sites->fetch(['org_id' => $org->id,]);
        if (!is_null($tag = $options['tag'])) {
            $this->sites->filterByTag($tag);
        }
        return $this->getRowsOfFields(
            $this->sites,
            ['message' => 'This organization has no sites.',]
        );
    }
}
