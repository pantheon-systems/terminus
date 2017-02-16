<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationUserMembership;

/**
 * Class OrganizationUserMemberships
 * @package Pantheon\Terminus\Collections
 */
class OrganizationUserMemberships extends OrganizationOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = OrganizationUserMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;
    /**
     * @var string
     */
    protected $url = 'organizations/{organization_id}/memberships/users';

    /**
     * Adds a user to this organization
     *
     * @param string $uuid UUID of user user to add to this organization
     * @param string $role Role to assign to the new member
     * @return Workflow $workflow
     */
    public function create($uuid, $role)
    {
        return $this->getOrganization()->getWorkflows()->create(
            'add_organization_user_membership',
            ['params' => ['user_email' => $uuid, 'role' => $role,],]
        );
    }
}
