<?php

namespace Pantheon\Terminus\Site;

use Terminus\Collections\Sites;

/**
 * Provides an interface for commands that need access to one or more Pantheon sites.
 *
 * Interface SiteAwareInterface
 * @package Pantheon\Terminus\Site
 */
interface SiteAwareInterface
{
    /***
     * @param Sites $sites
     * @return void
     */
    public function setSites(Sites $sites);

    /**
     * @return Sites The sites collection for the authenticated user.
     */
    public function sites();

    /**
     * Look up a site by id.
     *
     * @param $site_id
     * @return mixed
     */
    public function getSite($site_id);

    /**
     * Get the site and environment with the given ids.
     *
     * @param string $site_env_id The site/environment id in the form <site>[.<env>]
     * @param string $default_env The default environment to use if none is specified.
     * @return array The site and environment in an array.
     */
    public function getSiteEnv($site_env_id, $default_env);
}
