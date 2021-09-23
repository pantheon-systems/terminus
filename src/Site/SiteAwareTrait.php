<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SiteAwareTrait.
 *
 * Implements the SiteAwareInterface for dependency injection of the Sites collection.
 *
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
     * @return Sites
     *   The sites' collection for the authenticated user.
     */
    public function sites(): Sites
    {
        return $this->sites;
    }

    /**
     * Returns the site by site UUID, site name or `site-name.env`.
     *
     * @param string $site_id
     *   Either a site's UUID or its name or site_env.
     * @return Site
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getSite(string $site_id): Site
    {
        if (false !== strpos($site_id, '.')) {
            $site_env_parts = explode('.', $site_id);
            $site_id = $site_env_parts[0];
        }

        return $this->sites()->get($site_id);
    }

    /**
     * Returns the environment by `site-name.env`.
     *
     * @param string $site_env
     *
     * @return \Pantheon\Terminus\Models\Environment
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function getEnv(string $site_env): TerminusModel
    {
        if (false === strpos($site_env, '.')) {
            throw new TerminusException('The environment argument must be given as <site_name>.<environment>');
        }

        $site_env_parts = explode('.', $site_env);
        $site_id = $site_env_parts[0];
        $env_id = $site_env_parts[1];

        return $this->sites()->get($site_id)->getEnvironments()->get($env_id);
    }

    /**
     * @param string $site_env
     * @return TerminusModel|null
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function getOptionalEnv(string $site_env): ?TerminusModel
    {
        if (false === strpos($site_env, '.')) {
            return null;
        }

        return $this->getEnv($site_env);
    }

    /**
     * Verifies the site is not in the frozen state, throws an exception otherwise.
     *
     * @param string $site_env
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function requireSiteIsNotFrozen(string $site_env): void
    {
        if ($this->getSite($site_env)->isFrozen()) {
            throw new TerminusException(
                'This site is frozen. Its test and live environments and many commands will be '
                . 'unavailable while it remains frozen.'
            );
        }
    }
}
