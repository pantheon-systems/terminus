<?php


namespace Pantheon\Terminus\UnitTests\Collection;

class UserOrganizationMembershipTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/memberships/organizations';
    protected $class = 'Pantheon\Terminus\Collections\UserOrganizationMemberships';
}
