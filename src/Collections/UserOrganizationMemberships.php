<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Organization;
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
    protected $url = 'users/{user_id}/memberships/organizations';
    /**
     * @var string
     */
    protected $collected_class = UserOrganizationMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;

    /**
     * Retrieves models by either user ID, email address, or full name
     *
     * @param string $id Either an organization's UUID, name, or the UUID of the User-Organization join
     * @return UserOrganizationMembership
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            $org = $model->get('organization');
            $org_profile = $org->profile;
            if (in_array($id, [$org->id, $org_profile->name, $org_profile->machine_name,])) {
                return $model;
            }
        }
        throw new TerminusNotFoundException(
            'An organization of which you are a member, identified by "{id}", could not be found.',
            compact('id')
        );
    }
}
