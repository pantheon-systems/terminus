<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
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
        $workflow = $this->site->getWorkflows()->create(
            'add_site_user_membership',
            ['params' => ['user_email' => $email, 'role' => $role,],]
        );
        return $workflow;
    }

    /**
     * Retrieves the membership of the given UUID or email
     *
     * @param string $id UUID or email of desired user
     * @return SiteUserMembership
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            $user = $model->getUser();
            if (in_array($id, [$user->get('email'), $user->get('profile')->full_name])) {
                return $model;
            }
        }
        throw new TerminusNotFoundException('Cannot find site user with the name "{id}"', compact('id'));
    }
}
