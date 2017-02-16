<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Collections\UserOrganizationMemberships;

/**
 * Interface OrganizationsInterface
 * @package Pantheon\Terminus\Friends
 */
interface OrganizationsInterface
{
    /**
     * @return SiteOrganizationMemberships|UserOrganizationMemberships
     */
    public function getOrganizationMemberships();

    /**
     * @return Organization[] Returns an array of organizations to which this model belongs
     */
    public function getOrganizations();
}
