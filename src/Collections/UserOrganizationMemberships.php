<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\UserOrganizationMembership;

/**
 * Class UserOrganizationMemberships
 * @package Pantheon\Terminus\Collections
 */
class UserOrganizationMemberships extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = UserOrganizationMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/memberships/organizations';
}
