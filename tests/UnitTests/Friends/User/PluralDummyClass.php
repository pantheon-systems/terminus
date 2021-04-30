<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Friends\UsersInterface;
use Pantheon\Terminus\Friends\UsersTrait;

/**
 * Class PluralDummyClass
 * Testing aid for Pantheon\Terminus\Friends\UsersTrait & Pantheon\Terminus\Friends\UsersInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class PluralDummyClass implements UsersInterface
{
    use UsersTrait;

    /**
     * @var *UserMemberships
     */
    protected $user_memberships;

    /**
     * @return *UserMemberships
     */
    public function getUserMemberships()
    {
        return $this->user_memberships;
    }

    /**
     * @param *UserMemberships $user_memberships
     */
    public function setUserMemberships($user_memberships)
    {
        $this->user_memberships = $user_memberships;
    }
}
