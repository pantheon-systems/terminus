<?php

namespace Pantheon\Terminus\Friends;

/**
 * Class SitesTrait
 * @package Pantheon\Terminus\Friends
 */
trait SitesTrait
{
    /**
     * Returns all sites belonging to this model
     *
     * @return Site[]
     */
    public function getSites()
    {
        $sites = [];
        foreach ($this->getSiteMemberships()->all() as $membership) {
            $site = $membership->getSite();
            $sites[$site->id] = $site;
        }
        return $sites;
    }
}
