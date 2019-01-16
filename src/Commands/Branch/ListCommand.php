<?php

namespace Pantheon\Terminus\Commands\Branch;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Friends\RowsOfFieldsInterface;
use Pantheon\Terminus\Friends\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Branch
 */
class ListCommand extends TerminusCommand implements RowsOfFieldsInterface, SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Displays list of git branches for a site.
     *
     * @authorize
     *
     * @command branch:list
     * @aliases branches
     *
     * @field-labels
     *     id: ID
     *     sha: SHA
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @usage <site> Displays a list of Git branches within <site>'s Pantheon remote repository.
     */
    public function listBranches($site_id)
    {
        return $this->getRowsOfFields($this->getSite($site_id)->getBranches());
    }
}
