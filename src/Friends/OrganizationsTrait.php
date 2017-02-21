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
}
