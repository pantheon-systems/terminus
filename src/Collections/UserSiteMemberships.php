<?php

namespace Pantheon\Terminus\Collections;

/**
 * Class UserSiteMemberships
 * @package Pantheon\Terminus\Collections
 */
class UserSiteMemberships extends UserOwnedCollection
{

    /**
     * @var string
     */
    protected $url = 'users/{user_id}/memberships/sites';

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\UserSiteMembership';

    /**
     * @var boolean
     */
    protected $paged = true;
}
