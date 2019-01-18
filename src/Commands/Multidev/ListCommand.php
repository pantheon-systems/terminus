<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Lists a site's Multidev environments.
     *
     * @authorize
     *
     * @command multidev:list
     * @aliases multidevs
     *
     * @field-labels
     *     id: Name
     *     created: Created
     *     domain: Domain
     *     onserverdev: OnServer Dev?
     *     locked: Locked?
     *     initialized: Initialized?
     * @return RowsOfFields
     *
     * @param string $site_name Site name
     *
     * @usage <site> Lists <site>'s Multidev environments.
     */
    public function listMultidevs($site_name)
    {
        return $this->getRowsOfFields(
            $this->sites->get($site_name)->getEnvironments()->filterForMultidev(),
            ['message' => 'You have no multidev environments.',]
        );
    }
}
