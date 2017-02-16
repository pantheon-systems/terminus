<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Organization;

/**
 * Interface OrganizationJoinInterface
 * @package Pantheon\Terminus\Friends
 */
interface OrganizationJoinInterface
{
    /**
     * @return string[]
     */
    public function getReferences();

    /**
     * @return Organization Returns a Organization-type object
     */
    public function getOrganization();

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization);
}
