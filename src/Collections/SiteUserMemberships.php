<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\SiteUserMembership;

/**
 * Class SiteUserMemberships
 * @package Pantheon\Terminus\Collections
 */
class SiteUserMemberships extends SiteOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = SiteUserMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/memberships/users';

    /**
     * Adds this user as a member to the site
     *
     * @param string $email Email of team member to add
     * @param string $role  Role to assign to the new user
     * @return Workflow
     **/
    public function create($email, $role)
    {
        return $this->getSite()->getWorkflows()->create(
            'add_site_user_membership',
            ['params' => ['user_email' => $email, 'role' => $role,],]
        );
    }
}
