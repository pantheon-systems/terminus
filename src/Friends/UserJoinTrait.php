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
            $nickname = \uniqid(__FUNCTION__ . '-');
            $this->getContainer()
                ->add($nickname, User::class)
                ->addArgument($this->get('user'));
            $user = $this->getContainer()->get($nickname);
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
