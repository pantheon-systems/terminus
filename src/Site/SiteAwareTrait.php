<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SiteAwareTrait
 * Implements the SiteAwareInterface for dependency injection of the Sites collection.
 * @package Pantheon\Terminus\Site
 */
trait SiteAwareTrait
{
    /**
     * @var Sites
     */
    protected $sites;

    /***
     * @param Sites $sites
     * @return void
     */
    public function setSites(Sites $sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return Sites The sites collection for the authenticated user.
     */
    public function sites()
    {
        return $this->sites;
    }

    /**
     * Look up a site by id.
     *
     * @param Site $site_id Either a site's UUID or its name
     * @return mixed
     */
    public function getSite($site_id)
    {
        return $this->sites()->get($site_id);
    }

    /**
     * Get the site and environment with the given ids, if provided
     *
     * @param string $site_env_id The site/environment id in the form [<site>[.<env>]]
     * @return array The site and environment in an array, if provided; may return [null, null]
     */
    public function getOptionalSiteEnv($site_env_id)
    {
        if (empty($site_env_id)) {
            return [null, null];
        }

        list($site_id, $env_id) = array_pad(explode('.', $site_env_id), 2, null);

        $site = $this->getSite($site_id);
        $env = !empty($env_id) ? $site->getEnvironments()->get($env_id) : null;
        return [$site, $env];
    }

    /**
     * Get the site and environment with the given ids.
     *
     * @TODO This should be moved to the input/validation stage when that is available.
     *
     * @param string  $site_env_id The site/environment id in the form <site>[.<env>]
     * @param string  $default_env The default environment to use if none is specified
     * @return array  The site and environment in an array.
     * @throws TerminusException
     */
    public function getSiteEnv($site_env_id, $default_env = null)
    {
        list($site_id, $env_id) = array_pad(explode('.', $site_env_id), 2, null);
        $env_id = !empty($env_id) ? $env_id : $default_env;

        if (empty($site_id) || empty($env_id)) {
            throw new TerminusException('The environment argument must be given as <site_name>.<environment>');
        }

        $site = $this->getSite($site_id);
        $env = $site->getEnvironments()->get($env_id);
        return [$site, $env];
    }

    /**
     * Get the site and environment with the given IDs, provided the site is not frozen.
     *
     * @TODO This should be moved to the input/validation stage when that is available.
     *
     * @param string  $site_env_id The site/environment id in the form <site>[.<env>]
     * @param string  $default_env The default environment to use if none is specified
     * @return array  The site and environment in an array.
     * @throws TerminusException
     */
    public function getUnfrozenSiteEnv($site_env_id, $default_env = null)
    {
        list($site, $env) = $this->getSiteEnv($site_env_id, $default_env);

        if ($site->isFrozen()) {
            throw new TerminusException(
                'This site is frozen. Its test and live environments and many commands will be '
                . 'unavailable while it remains frozen.'
            );
        }

        return [$site, $env,];
    }
}
