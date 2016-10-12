<?php

namespace Pantheon\Terminus\Commands\Org;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

class ListCommand extends TerminusCommand
{
    /**
     * Lists the organizations of which the current user is a member
     *
     * @authorized
     *
     * @command org:list
     * @aliases orgs
     *
     * @field-labels
     *   id: ID
     *   name: Name
     *
     * @return RowsOfFields
     *
     * @usage terminus org:list
     *   Display a list of organizations which the logged-in user is a member of
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
