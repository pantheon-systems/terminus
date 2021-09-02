<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\User;

/**
 * Class UserJoinTrait
 * @package Pantheon\Terminus\Friends
 */
trait UserJoinTrait
{
    /**
     * @var User
     */
    private $user;

    /**
     * @inheritdoc
     */
    public function getReferences()
    {
        return array_merge(parent::getReferences(), $this->getUser()->getReferences());
    }

    /**
     * @inheritdoc
     */
    public function getUser()
    {
        if (empty($this->user)) {
            $user = new User($this->get('user'));
            $this->getContainer()->inflect($user);
            $user->memberships = [$this];
            $this->setUser($user);
        }
        return $this->user;
    }

    /**
     * @inheritdoc
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
