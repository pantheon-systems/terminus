<?php

namespace Pantheon\Terminus\Friends;

/**
 * Class OrganizationsTrait
 * @package Pantheon\Terminus\Friends
 */
trait OrganizationsTrait
{
    /**
     * Returns all organization members of this site
     *
     * @return Organization[]
     */
    public function getOrganizations()
    {
        $orgs = [];
        foreach ($this->getOrganizationMemberships()->all() as $membership) {
            $org = $membership->getOrganization();
            $orgs[$org->id] = $org;
        }
        return $orgs;
    }

    /**
     * @return SiteOrganizationMemberships|UserOrganizationMemberships
     * @deprecated 1.0.1 Please use getOrganizationMemberships() instead.
     */
    public function getOrgMemberships()
    {
        return $this->getOrganizationMemberships();
    }
}
