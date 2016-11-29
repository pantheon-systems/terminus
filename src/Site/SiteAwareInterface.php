<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Collections\Sites;

/**
 * Interface SiteAwareInterface
 * Provides an interface for commands that need access to one or more Pantheon sites.
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
     * @param string $default_env The default environment to use if none is specified; null if not required
     * @return array The site and environment in an array.
     */
    public function getSiteEnv($site_env_id, $default_env);

    /**
     * Get the site and environment with the given ids if provided
     *
     * @param string $site_env_id The site/environment id in the form <site>[.<env>]
     * @return array The site and environment in an array, if provided; may return [null, null]
     */
    public function getOptionalSiteEnv($site_env_id);
}
