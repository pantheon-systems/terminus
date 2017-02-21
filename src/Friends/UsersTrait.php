<?php

namespace Pantheon\Terminus\Friends;

/**
 * Class UsersTrait
 * @package Pantheon\Terminus\Friends
 */
trait UsersTrait
{
    /**
     * Returns all users belonging to this model
     *
     * @return User[]
     */
    public function getUsers()
    {
        $users = [];
        foreach ($this->getUserMemberships()->all() as $membership) {
            $user = $membership->getUser();
            $users[$user->id] = $user;
        }
        return $users;
    }
}
