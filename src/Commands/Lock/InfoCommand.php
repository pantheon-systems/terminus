<?php

namespace Pantheon\Terminus\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\PublicApi\Client;
use Pantheon\Terminus\PublicApi\PublicApiAwareInterface;
use Pantheon\Terminus\PublicApi\SiteEnvResolver;

/**
 * Class InfoCommand.
 *
 * Uses the Public API v1: the lock fields of the environment from GET /sites/{siteId}/environments.
 *
 * @package Pantheon\Terminus\Commands\Lock
 */
class InfoCommand extends TerminusCommand implements PublicApiAwareInterface
{
    private Client $publicApi;

    public function setPublicApiClient(Client $client): void
    {
        $this->publicApi = $client;
    }

    /**
     * Displays HTTP basic authentication status and configuration for the environment.
     *
     * @interact
     *
     * @command lock:info
     *
     * @field-labels
     *     locked: Locked?
     *     username: Username
     *     password: Password
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays HTTP basic authentication status and configuration for <site>'s <env> environment.
     *
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     */
    public function info($site_env)
    {
        $environment = (new SiteEnvResolver($this->publicApi))->resolve($site_env)->environment;

        // Output contract (same field names and types as 4.x): v1 isLocked/username/password.
        $list = new PropertyList([
            'locked' => (bool) ($environment['isLocked'] ?? false),
            'username' => $environment['username'] ?? null,
            'password' => $environment['password'] ?? null,
        ]);
        // Table output prints booleans as true/false, as StructuredListTrait does in 4.x.
        $list->addRendererFunction(function ($key, $cellData) {
            return is_bool($cellData) ? ($cellData ? 'true' : 'false') : $cellData;
        });
        return $list;
    }
}
