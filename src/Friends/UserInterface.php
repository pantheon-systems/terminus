<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\User;

/**
 * Interface UserInterface
 * @package Pantheon\Terminus\Friends
 */
interface UserInterface
{
    /**
     * @return User Returns a User-type object
     */
    public function getUser();

    /**
     * @param User $user
     */
    public function setUser(User $user);
}
