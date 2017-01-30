<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Site\Org
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the list of supporting organizations associated with a site.
     *
     * @authorize
     *
     * @command site:org:list
     * @aliases site:orgs
     *
     * @field-labels
     *     org_name: Name
     *     org_id: ID
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @usage <site> Displays the list of supporting organizations associated with <site>.
     */
    public function listOrgs($site_id)
    {
        $orgs = $this->getSite($site_id)->getOrganizationMemberships()->serialize();

        if (empty($orgs)) {
            $this->log()->notice('This site has no supporting organizations.');
        }
        return new RowsOfFields($orgs);
    }
}
