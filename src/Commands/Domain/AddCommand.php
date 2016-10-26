<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Adds a domain to the environment
     *
     * @command domain:add
     *
     * @param string $site_env Site & environment to add a domain to, in the form `site-name.env`.
     * @param string $domain   The domain to associate with this environment
     *
     * @usage terminus domain:add <site_name>.<env_id> <domain_name>
     *     Attaches <domain_name> to <site name> site's <env_id> environment
     */
    public function add($site_env, $domain)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $env->hostnames->create($domain);
        $this->log()->notice(
            'Added {domain} to {site}.{env}',
            ['domain' => $domain, 'site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
