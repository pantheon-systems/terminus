<?php

namespace Pantheon\Terminus\Commands\Branch;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Branch
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List the Git branches for a site
     *
     * @authorize
     *
     * @command branch:list
     * @aliases branches
     *
     * @field-labels
     *   id: ID
     *   sha: SHA
     * @return RowsOfFields
     *
     * @param string $site_id The name of the site to list the branches of
     *
     * @usage terminus branch:list <site>
     *    Lists the Git branches on the Pantheon remote associated with <site>
     */
    public function listBranches($site_id)
    {
        $site = $this->getSite($site_id);
        $branches = array_map(
            function ($branch) {
                return $branch->serialize();
            },
            $site->getBranches()->all()
        );
        return new RowsOfFields($branches);
    }
}
