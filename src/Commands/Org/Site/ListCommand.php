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
     *     service_level: Service Level
     *     framework: Framework
     *     owner: Owner
     *     created: Created
     *     tags: Tags
     * @return RowsOfFields
     *
     * @param string $organization Organization name or ID
     * @option string $tag Tag name to filter
     *
     * @usage terminus org:site:list <organization>
     *     Displays the list of sites associated with <organization>.
     * @usage terminus org:site:list <organization> --tag=<tag>
     *     Displays the list of sites associated with <organization> that have the <tag> tag.
     */
    public function listSites($organization, $options = ['tag' => null,])
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $this->sites->fetch(['org_id' => $org->id,]);
        if (!is_null($tag = $options['tag'])) {
            $this->sites->filterByTag($tag);
        }
        $sites = $this->sites->serialize();

        if (empty($sites)) {
            $this->log()->notice('This organization has no sites.');
        }

        return new RowsOfFields($sites);
    }
}
