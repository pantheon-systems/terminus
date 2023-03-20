<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\SiteUserMembership;

/**
 * Class SiteUserMemberships.
 *
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
     * Adds this user as a member to the site.
     *
     * @param string $member
     *   Email or uuid of team member to add.
     * @param string $role
     *   Role to assign to the new user.
     *
     * @return \Pantheon\Terminus\Models\Workflow
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($member, $role)
    {
        $workflow_name = 'add_site_user_membership';
        $params = [
            'params' => [
                'role' => $role
            ],
        ];
        if (preg_match(
            '/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/',
            $member
        )) {
            $params['params']['user_id'] = $member;
            $workflow_name = 'add_site_user_membership_by_uuid';
        } else {
            $params['params']['user_email'] = $member;
        }
        return $this->getSite()->getWorkflows()->create(
            $workflow_name,
            $params
        );
    }
}
