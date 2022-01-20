<?php

namespace Pantheon\Terminus\Friends;

/**
 * Class SitesTrait.
 *
 * @package Pantheon\Terminus\Friends
 */
trait SitesTrait
{
    /**
     * Returns all sites belonging to this model.
     *
     * @return \Pantheon\Terminus\Models\Site[]
     */
    public function getSites(): array
    {
        $sites = [];
        foreach ($this->getSiteMemberships()->all() as $membership) {
            /** @var \Pantheon\Terminus\Models\SiteOrganizationMembership|\Pantheon\Terminus\Models\SiteUserMembership $membership */
            $site = $membership->getSite();
            $sites[$site->id] = $site;
        }
        return $sites;
    }
}
