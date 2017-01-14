<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\UserOrganizationMemberships;

/**
 * Class UserOrganizationMembershipTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UserOrganizationMembershipTest extends UserOwnedCollectionTest
{
    /**
     * @var string
     */
    protected $class = UserOrganizationMemberships::class;
    /**
     * @var string
     */
    protected $url = 'users/USERID/memberships/organizations';
}
