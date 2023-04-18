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
     * @deprecated Use fetchSite() instead.
     *
     * @param string $site_id
     *   Either a site's UUID or its name or site_env.
     *
     * @return Site
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getSite(string $site_id): Site
    {
        return $this->fetchSite($site_id);
    }

    /**
     * Returns the site by site UUID, site name or `site-name.env`.
     *
     * @param string $site_id
     *   Either a site's UUID or its name or site_env.
     *
     * @return Site
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function fetchSite(string $site_id): Site
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
     * @param string|null $default_env
     *
     * @return \Pantheon\Terminus\Models\Environment
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getEnv(string $site_env, ?string $default_env = null): TerminusModel
    {
        if (null === $default_env && false === strpos($site_env, '.')) {
            throw new TerminusException('The environment argument must be given as <site_name>.<environment>');
        }

        $site_env_parts = explode('.', $site_env);
        $site_id = $site_env_parts[0];
        $env_id = $site_env_parts[1] ?? $default_env;

        return $this->sites()->get($site_id)->getEnvironments()->get($env_id);
    }

    /**
     * Returns the environment if provided in a form of `site-name.env`.
     *
     * @param string $site_env
     * @param string|null $default_env
     *
     * @return TerminusModel|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getOptionalEnv(string $site_env, ?string $default_env = null): ?TerminusModel
    {
        if (null === $default_env && false === strpos($site_env, '.')) {
            return null;
        }

        return $this->getEnv($site_env, $default_env);
    }

    /**
     * Verifies the site is not in the frozen state, throws an exception otherwise.
     *
     * @param string $site_env
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function requireSiteIsNotFrozen(string $site_env): void
    {
        if ($this->fetchSite($site_env)->isFrozen()) {
            throw new TerminusException(
                'This site is frozen. Its test and live environments and many commands will be '
                . 'unavailable while it remains frozen.'
            );
        }
    }

    /**
     * Get the site and environment by `site-name.env`.
     *
     * @deprecated
     *   Use $this->getOptionalEnv($site_env).
     *
     * @param string $site_env
     *   The site/environment id in the form [<site>[.<env>]].
     *
     * @return array
     *   The site and environment in an array, if provided; may return [null, null].
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     */
    public function getOptionalSiteEnv(string $site_env): array
    {
        try {
            $site = $this->fetchSite($site_env);
        } catch (TerminusException $e) {
            return [null, null];
        }

        try {
            $env = $this->getEnv($site_env);
        } catch (TerminusException $e) {
            return [$site, null];
        }

        return [$site, $env];
    }

    /**
     * Get the site and environment by `site-name.env`.
     *
     * @deprecated
     *   Use $this->getSite($site_env) and $this->getEnv($site_env).
     *
     * @param string $site_env
     *   The site/environment id in the form <site>[.<env>].
     * @param string|null $default_env
     *   The default environment to use if none is specified.
     *
     * @return array
     *   The site and environment in an array.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     */
    public function getSiteEnv(string $site_env, ?string $default_env = null): array
    {
        return [
            $this->fetchSite($site_env),
            $this->getOptionalEnv($site_env, $default_env),
        ];
    }

    /**
     * Get the site and environment by `site-name.env`, provided the site is not frozen.
     *
     * @deprecated
     *   Use $this->requireSiteIsNotFrozen($site_env) in conjunction with $this->fetchSite($site_env) and/or
     *   $this->getEnv($site_env)/$this->getOptionalEnv($site_env).
     *
     * @param string $site_env
     *   The site/environment id in the form <site>[.<env>].
     * @param string|null $default_env
     *   The default environment to use if none is specified.
     *
     * @return array
     *   The site and environment in an array.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     */
    public function getUnfrozenSiteEnv(string $site_env, ?string $default_env = null): array
    {
        $this->requireSiteIsNotFrozen($site_env);

        return [
            $this->fetchSite($site_env),
            $this->getOptionalEnv($site_env, $default_env),
        ];
    }
}
