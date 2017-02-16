<?php

namespace Pantheon\Terminus\Friends;

/**
 * Interface UsersInterface
 * @package Pantheon\Terminus\Friends
 */
interface UsersInterface
{
    /**
     * @return OrganizationUserMemberships|SiteUserMemberships
     */
    public function getUserMemberships();

    /**
     * @return Site[] Returns an array of users which belong to this model
     */
    public function getUsers();
}
