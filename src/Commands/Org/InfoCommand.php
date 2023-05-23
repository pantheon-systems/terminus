<?php

namespace Pantheon\Terminus\Commands\Org;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Organization\OrganizationAwareTrait;

class InfoCommand extends OrgCommand
{
    use StructuredListTrait;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Displays information about an organization.
     *
     * @authorize
     *
     * @command org:info
     * @field-labels
     *     id: ID
     *     name: Name
     *     label: Label
     *     created: Created
     *     region: Region
     * @return PropertyList
     *
     * @aliases org
     *
     * @param string $organization Organization name, label, or ID
     *
     * @usage Displays information about an organization.
     */
    public function info($organization)
    {
        return $this->getPropertyList($this->getOrganization($organization));
    }
}
