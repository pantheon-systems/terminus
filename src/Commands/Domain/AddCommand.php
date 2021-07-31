<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Associates a domain with the environment.
     *
     * @authorize
     *
     * @command domain:add
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain Domain e.g. `example.com`
     *
     * @usage <site>.<env> <domain_name> Associates <domain_name> with <site>'s <env> environment.
     */
    public function add($site_env, $domain)
    {
        [$site, $env] = explode('.', $site_env);
        if (empty($site) || empty($env) || empty($domain)) {
            throw new TerminusNotFoundException(
                'The Site and environment must take the form of {site}.{env} followed by the domain name you are adding'
            );
        }

        $env = $this->sites()->get($site)->getEnvironments()->get($env) ?? null;
        if (!$env instanceof Environment) {
            throw new TerminusNotFoundException(
                'Site/env not found {site}.{env}',
                ['site' => $site, 'env' => $env]
            );
        }
        $result = $env->getDomains()->create($domain);

        $this->log()->notice(
            'Added {domain} to {site}.{env}',
            ['domain' => $domain, 'site' => $site, 'env' => $env]
        );
    }
}
