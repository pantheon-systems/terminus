<?php

namespace Pantheon\Terminus\Friends;

/**
 * Interface SitesInterface
 * @package Pantheon\Terminus\Friends
 */
interface SitesInterface
{
    /**
     * @return OrganizationSiteMemberships|UserSiteMemberships
     */
    public function getSiteMemberships();

    /**
     * @return Site[] Returns an array of sites which belong to this model
     */
    public function getSites();
}
