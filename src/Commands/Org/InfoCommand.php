<?php

namespace Pantheon\Terminus\Commands\Org;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Organization\OrganizationAwareInterface;
use Pantheon\Terminus\Organization\OrganizationAwareTrait;

class InfoCommand extends TerminusCommand implements OrganizationAwareInterface
{
    use StructuredListTrait;
    use OrganizationAwareTrait;

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
     *
     * @param string $organization Organization name, label, or ID
     *
     * @usage <organization> Displays information about an organization.
     * @return PropertyList
     *
     * @aliases org
     *
     */
    public function info(string $organization)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get(
            $organization
        )->getOrganization();
        return new PropertyList($org->serialize());
    }
}
