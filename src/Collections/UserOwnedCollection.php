<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;

/**
 * Class UserOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class UserOwnedCollection extends APICollection implements UserInterface
{
    use UserTrait;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setUser($options['user']);
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        // Replace the {user_id} token with the actual user id.
        return str_replace('{user_id}', $this->getUser()->id, parent::getUrl());
    }
}
