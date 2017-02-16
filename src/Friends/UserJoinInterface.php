<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\User;

/**
 * Interface UserJoinInterface
 * @package Pantheon\Terminus\Friends
 */
interface UserJoinInterface
{
    /**
     * @return string[]
     */
    public function getReferences();

    /**
     * @return User Returns a User-type object
     */
    public function getUser();

    /**
     * @param User $user
     */
    public function setUser(User $user);
}
