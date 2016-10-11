<?php


namespace Pantheon\Terminus\UnitTests\Collection;

class UserSiteMembershipsTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/memberships/sites';
    protected $class = 'Pantheon\Terminus\Collections\UserSiteMemberships';
}
