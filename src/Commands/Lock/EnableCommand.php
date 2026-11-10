<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\PublicApi\Client;
use Pantheon\Terminus\PublicApi\PublicApiAwareInterface;
use Pantheon\Terminus\PublicApi\SiteEnvResolver;
use Pantheon\Terminus\PublicApi\WorkflowWaiter;

/**
 * Class EnableCommand.
 *
 * Uses the Public API v1: POST /sites/{siteId}/environments/{env}/lock.
 *
 * @package Pantheon\Terminus\Commands\Lock
 */
class EnableCommand extends TerminusCommand implements PublicApiAwareInterface
{
    private Client $publicApi;

    public function setPublicApiClient(Client $client): void
    {
        $this->publicApi = $client;
    }

    /**
     * Enables HTTP basic authentication on the environment.
     * Note: HTTP basic authentication username and password are stored in plaintext.
     *
     * @interact
     *
     * @command lock:enable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $username HTTP basic authentication username
     * @param string $password HTTP basic authentication password
     * @usage <site>.<env> <username> <password> Enables HTTP basic authentication on <site>'s <env> environment with the username <username> and the password <password>.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function enable($site_env, $username, $password)
    {
        $siteEnv = (new SiteEnvResolver($this->publicApi))->resolve($site_env);

        $payload = $this->publicApi->post(
            Client::path('/sites/%s/environments/%s/lock', $siteEnv->siteId, $siteEnv->envName),
            [
                'siteId' => $siteEnv->siteId,
                'environment' => $siteEnv->envName,
                'username' => $username,
                'password' => $password,
            ]
        );
        $waiter = new WorkflowWaiter(
            $this->publicApi,
            (int) $this->getConfig()->get('workflow_polling_delay_ms', WorkflowWaiter::DEFAULT_POLL_DELAY_MS)
        );
        $waiter->wait($siteEnv->siteId, (array) $payload);

        $this->log()->notice(
            '{site}.{env} has been locked.',
            ['site' => $siteEnv->siteName, 'env' => $siteEnv->envName]
        );
    }
}
