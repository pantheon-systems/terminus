<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Models\UserOrganizationMembership;

/**
 * Class UserOrganizationMemberships
 * @package Pantheon\Terminus\Collections
 */
class UserOrganizationMemberships extends UserOwnedCollection
{
    use OrganizationsTrait;

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

    /**
     * @return array|void
     */
    public function serialize()
    {
        return array_map(
            function ($member) {
                return $member->getOrganization()->serialize();
            },
            $this->all()
        );
    }
}
