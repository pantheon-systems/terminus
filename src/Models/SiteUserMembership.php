<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;
use Pantheon\Terminus\Friends\UserJoinInterface;
use Pantheon\Terminus\Friends\UserJoinTrait;

/**
 * Class SiteUserMembership
 * @package Pantheon\Terminus\Models
 */
class SiteUserMembership extends TerminusModel implements ContainerAwareInterface, SiteInterface, UserJoinInterface
{
    use ContainerAwareTrait;
    use SiteTrait;
    use UserJoinTrait;

    const PRETTY_NAME = 'site-user membership';

    /**
     * Remove membership, either org or user
     *
     * @return Workflow
     **/
    public function delete()
    {
        return $this->getSite()->getWorkflows()->create(
            'remove_site_user_membership',
            ['params' => ['user_id' =>  $this->getUser()->id,],]
        );
    }

    /**
     * Determines whether this user is the owner of the site.
     *
     * @return bool
     */
    public function isOwner()
    {
        return $this->getUser()->id === $this->getSite()->get('owner');
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        $user = $this->getUser()->serialize();
        return $user + [
            'is_owner' => $this->isOwner(),
            'role'  => $this->get('role'),
        ];
    }

    /**
     * Changes the role of the given member
     *
     * @param string $role Desired role for this member
     * @return Workflow
     */
    public function setRole($role)
    {
        return $this->getSite()->getWorkflows()->create(
            'update_site_user_membership',
            ['params' => ['user_id' =>  $this->getUser()->id, 'role' => $role,],]
        );
    }
}
