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

    public static $pretty_name = 'site-user membership';

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

    public function serialize()
    {
        $user = $this->getUser()->serialize();
        return $user + ['role'  => $this->get('role'),];
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
