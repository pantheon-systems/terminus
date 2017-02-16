<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Organization;

/**
 * Interface OrganizationInterface
 * @package Pantheon\Terminus\Friends
 */
interface OrganizationInterface
{
    /**
     * @return Organization Returns a Organization-type object
     */
    public function getOrganization();

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization);
}
