<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Traits\BuildPathTrait;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;

/**
 * Manages the build path (monorepo subdirectory) for an eVCS site.
 */
class BuildPathCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use VcsClientAwareTrait;
    use BuildPathTrait;

    /**
     * Displays the build path for a site.
     *
     * The build path is the subdirectory within the repository that the site
     * builds from (used for monorepos). An empty value means the repository root.
     *
     * @authorize
     *
     * @command site:build-path
     * @aliases site:build-path:get
     *
     * @param string $site Site name or ID
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site> Displays the build path for <site>.
     */
    public function show($site)
    {
        $site_obj = $this->resolveEvcsSite($site);

        $data = $this->getVcsClient()->getSiteDetailsById($site_obj->id);
        $this->assertSuccess($data, 'Error fetching site details');

        $details = (array) ($data['data'][0] ?? []);
        $build_path = $details['build_path'] ?? '';

        if ($build_path === '') {
            $this->log()->notice('{site} builds from the repository root.', ['site' => $site_obj->getName()]);
        } else {
            $this->log()->notice(
                '{site} builds from: {build_path}',
                ['site' => $site_obj->getName(), 'build_path' => $build_path]
            );
        }

        return $build_path;
    }

    /**
     * Sets the build path (monorepo subdirectory) for a site.
     *
     * Omit <path> (or pass an empty string) to reset the site to build from the
     * repository root.
     *
     * @authorize
     *
     * @command site:build-path:set
     *
     * @param string $site Site name or ID
     * @param string $path Repository-relative path to build from (e.g. apps/web). Empty resets to repo root.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site> apps/web Sets <site> to build from the apps/web subdirectory.
     * @usage <site> Resets <site> to build from the repository root.
     */
    public function set($site, $path = '')
    {
        $error = self::validateBuildPath($path);
        if ($error !== null) {
            throw new TerminusException('Invalid build path: {error}', ['error' => $error]);
        }

        $site_obj = $this->resolveEvcsSite($site);

        $data = $this->getVcsClient()->updateBuildPath($site_obj->id, $path);
        $this->assertSuccess($data, 'Error updating build path');

        if ($path === '') {
            $this->log()->notice(
                'Build path for {site} has been reset to the repository root.',
                ['site' => $site_obj->getName()]
            );
        } else {
            $this->log()->notice(
                'Build path for {site} has been set to {build_path}.',
                ['site' => $site_obj->getName(), 'build_path' => $path]
            );
        }
    }

    /**
     * Resolves a site argument to a Site object, ensuring it is an eVCS site.
     *
     * @param string $site
     * @return \Pantheon\Terminus\Models\Site
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function resolveEvcsSite($site)
    {
        $site_env = "{$site}.dev";
        $this->requireSiteIsNotFrozen($site_env);
        $site_obj = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if (!$env->isEvcsSite()) {
            throw new TerminusException('This command only works for eVCS sites.');
        }

        return $site_obj;
    }

    /**
     * Throws if a VCS API response did not indicate success.
     *
     * @param array $data
     * @param string $context
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function assertSuccess(array $data, string $context): void
    {
        if (isset($data['error'])) {
            throw new TerminusException('{context}: {error}', ['context' => $context, 'error' => $data['error']]);
        }
        if (($data['success'] ?? false) !== true) {
            throw new TerminusException(
                '{context}: {error}',
                ['context' => $context, 'error' => $data['message'] ?? 'unknown error']
            );
        }
    }
}
