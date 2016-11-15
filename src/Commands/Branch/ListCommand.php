<?php

namespace Pantheon\Terminus\Commands\Branch;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List the git branches for a site.
     *
     * @command branch:list
     *
     * @param string $site_id The name of the site.
     *
     * @field-labels
     *   id: ID
     *   sha: SHA
     *
     * @return \Pantheon\Terminus\Commands\Branch\RowsOfFields
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
