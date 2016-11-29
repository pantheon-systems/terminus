<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\User;

/**
 * Class UserOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
class UserOwnedCollection extends TerminusCollection
{
    protected $user;

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setUser($options['user']);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        // Replace the {user_id} token with the actual user id.
        return str_replace('{user_id}', $this->getUser()->get('id'), parent::getUrl());
    }
}
