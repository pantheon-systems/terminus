<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Site;

/**
 * Interface SiteInterface
 * @package Pantheon\Terminus\Friends
 */
interface SiteInterface
{
    /**
     * @return Site Returns a Site-type object
     */
    public function getSite();

    /**
     * @param Site $site
     */
    public function setSite(Site $site);
}
