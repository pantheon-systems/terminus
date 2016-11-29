<?php

namespace Pantheon\Terminus\UnitTests\Collections;

/**
 * Class UserSiteMembershipsTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UserSiteMembershipsTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/memberships/sites';
    protected $class = 'Pantheon\Terminus\Collections\UserSiteMemberships';
}
