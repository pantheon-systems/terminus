<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Models\Site;

/**
 * Interface SiteAwareInterface.
 *
 * Provides an interface for commands that need access to one or more Pantheon sites.
 *
 * @package Pantheon\Terminus\Site
 */
interface SiteAwareInterface
{
    /***
     * Sets the sites.
     *
     * @param Sites $sites
     */
    public function setSites(Sites $sites);

    /**
     * Returns the sites.
     *
     * @return Sites
     *   The sites' collection for the authenticated user.
     */
    public function sites(): Sites;

    /**
     * Returns the site by site id, name or site_env.
     *
     * @deprecated Use fetchSite() instead.
     *
     * @param string $site_id
     *
     * @return mixed
     */
    public function getSite(string $site_id): Site;

    /**
     * Returns the site by site id, name or site_env.
     *
     * @param string $site_id
     *
     * @return mixed
     */
    public function fetchSite(string $site_id): Site;
}
