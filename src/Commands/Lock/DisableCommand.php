<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\PublicApi\Client;
use Pantheon\Terminus\PublicApi\PublicApiAwareInterface;
use Pantheon\Terminus\PublicApi\SiteEnvResolver;
use Pantheon\Terminus\PublicApi\WorkflowWaiter;

/**
 * Class DisableCommand.
 *
 * Uses the Public API v1: DELETE /sites/{siteId}/environments/{env}/lock.
 *
 * @package Pantheon\Terminus\Commands\Lock
 */
class DisableCommand extends TerminusCommand implements PublicApiAwareInterface
{
    private Client $publicApi;

    public function setPublicApiClient(Client $client): void
    {
        $this->publicApi = $client;
    }

    /**
     * Disables HTTP basic authentication on the environment.
     *
     * @interact
     *
     * @command lock:disable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @usage <site>.<env> Disables HTTP basic authentication on <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function disable($site_env)
    {
        $siteEnv = (new SiteEnvResolver($this->publicApi))->resolve($site_env);

        $payload = $this->publicApi->delete(
            Client::path('/sites/%s/environments/%s/lock', $siteEnv->siteId, $siteEnv->envName)
        );
        $waiter = new WorkflowWaiter(
            $this->publicApi,
            (int) $this->getConfig()->get('workflow_polling_delay_ms', WorkflowWaiter::DEFAULT_POLL_DELAY_MS)
        );
        $waiter->wait($siteEnv->siteId, (array) $payload);

        $this->log()->notice(
            '{site}.{env} has been unlocked.',
            ['site' => $siteEnv->siteName, 'env' => $siteEnv->envName]
        );
    }
}
