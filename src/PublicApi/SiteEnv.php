<?php

namespace Pantheon\Terminus\PublicApi;

/**
 * A resolved `site.env` argument: the site's ID and name plus the v1 Environment node.
 */
class SiteEnv
{
    public string $siteId;

    public string $siteName;

    public string $envName;

    /**
     * The v1 Environment object (id, name, isLocked, username, password, ...).
     */
    public array $environment;

    public function __construct(string $siteId, string $siteName, string $envName, array $environment)
    {
        $this->siteId = $siteId;
        $this->siteName = $siteName;
        $this->envName = $envName;
        $this->environment = $environment;
    }
}
