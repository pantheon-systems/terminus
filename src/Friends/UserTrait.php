<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\User;

/**
 * Class UserTrait
 * @package Pantheon\Terminus\Friends
 */
trait UserTrait
{
    /**
     * @var User
     */
    private $user;

    /**
     * @return User Returns a User-type object
     */
    public function getUser()
    {
        if (empty($this->user) && isset($this->collection)) {
            $this->setUser($this->collection->getUser());
        }
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
