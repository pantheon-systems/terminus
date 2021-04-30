<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Friends\SitesInterface;
use Pantheon\Terminus\Friends\SitesTrait;

/**
 * Class PluralDummyClass
 * Testing aid for Pantheon\Terminus\Friends\SitesTrait & Pantheon\Terminus\Friends\SitesInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class PluralDummyClass implements SitesInterface
{
    use SitesTrait;

    /**
     * @var *SiteMemberships
     */
    protected $site_memberships;

    /**
     * @return *SiteMemberships
     */
    public function getSiteMemberships()
    {
        return $this->site_memberships;
    }

    /**
     * @param *SiteMemberships $site_memberships
     */
    public function setSiteMemberships($site_memberships)
    {
        $this->site_memberships = $site_memberships;
    }
}
