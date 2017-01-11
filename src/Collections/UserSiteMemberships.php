<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\UserSiteMembership;

/**
 * Class UserSiteMemberships
 * @package Pantheon\Terminus\Collections
 */
class UserSiteMemberships extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = UserSiteMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/memberships/sites';
}
