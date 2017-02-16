<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Site;

/**
 * Interface SiteJoinInterface
 * @package Pantheon\Terminus\Friends
 */
interface SiteJoinInterface
{
    /**
     * @return string[]
     */
    public function getReferences();

    /**
     * @return Site Returns a Site-type object
     */
    public function getSite();

    /**
     * @param Site $site
     */
    public function setSite(Site $site);
}
