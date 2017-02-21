<?php

namespace Pantheon\Terminus\Commands\Org;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org
 */
class ListCommand extends TerminusCommand
{
    /**
     * Displays the list of organizations.
     *
     * @authorize
     *
     * @command org:list
     * @aliases orgs
     *
     * @field-labels
     *     id: ID
     *     name: Name
     *     label: Label
     * @return RowsOfFields
     *
     * @usage Displays the list of organizations.
     */
    public function listOrgs()
    {
        $orgs = array_map(
            function ($org) {
                return $org->serialize();
            },
            $this->session()->getUser()->getOrganizations()
        );

        if (empty($orgs)) {
            $this->log()->warning('You are not a member of any organizations.');
        }

        return new RowsOfFields($orgs);
    }
}
