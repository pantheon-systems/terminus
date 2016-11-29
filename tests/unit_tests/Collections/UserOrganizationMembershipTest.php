<?php

namespace Pantheon\Terminus\UnitTests\Collections;

/**
 * Class UserOrganizationMembershipTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UserOrganizationMembershipTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/memberships/organizations';
    protected $class = 'Pantheon\Terminus\Collections\UserOrganizationMemberships';
}
