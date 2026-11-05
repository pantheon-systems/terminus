<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;

/**
 * Class PauseBuildCommand.
 *
 * @package Pantheon\Terminus\Commands
 */
class PauseBuildCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use VcsClientAwareTrait;

    /**
     * Pauses build for a given site
     *
     * @authorize
     *
     * @command site:pause-builds
     * @aliases pause-builds
     *
     * @param string $site Site name or ID
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site> Pauses build for <site>.
     */
    public function pauseBuilds($site)
    {
        // User confirmation of experimental command
        if (
            !$this->confirm(
                'This command is experimental and not intended for regular use. Are you sure you want to proceed?'
            )
        ) {
            return;
        }

        $site_env = "{$site}.dev";
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if (!$env->isEvcsSite()) {
            throw new TerminusException('This command only works for eVCS sites.');
        }

        $data = $this->getVcsClient()->pauseBuild($site->id);
        if (isset($data['error'])) {
            throw new TerminusException('Error pausing build: {error}', ['error' => $data['error']]);
        }
        if ($data['success'] !== true) {
            throw new TerminusException('Error pausing build: {error}', ['error' => $data['message']]);
        }
        $this->log()->notice('Build for {site} has been paused.', ['site' => $site->getName()]);

        $this->log()->notice('Fetching site details...');
        $data = $this->getVcsClient()->getSiteDetailsById($site->id);
        if (isset($data['error'])) {
            throw new TerminusException('Error fetching site details: {error}', ['error' => $data['error']]);
        }
        if ($data['success'] !== true) {
            throw new TerminusException('Error fetching site details: {error}', ['error' => $data['message']]);
        }
        $details = $data['data'][0];
        $this->log()->notice('Your installation id is: {installation_id}', [
            'installation_id' => $details->installation_id,
        ]);
    }
}
