<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Friends\OrganizationsInterface;
use Pantheon\Terminus\Friends\OrganizationsTrait;

/**
 * Class PluralDummyClass
 * Testing aid for Pantheon\Terminus\Friends\OrganizationsTrait & Pantheon\Terminus\Friends\OrganizationsInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class PluralDummyClass implements OrganizationsInterface
{
    use OrganizationsTrait;

    /**
     * @var *OrganizationMemberships
     */
    protected $organization_memberships;

    /**
     * @return *OrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        return $this->organization_memberships;
    }

    /**
     * @param *OrganizationMemberships $organization_memberships
     */
    public function setOrganizationMemberships($organization_memberships)
    {
        $this->organization_memberships = $organization_memberships;
    }
}
