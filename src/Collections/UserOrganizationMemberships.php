<?php

namespace Pantheon\Terminus\Collections;

class UserOrganizationMemberships extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/memberships/organizations';

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\UserOrganizationMembership';

    /**
     * @var boolean
     */
    protected $paged = true;
}
