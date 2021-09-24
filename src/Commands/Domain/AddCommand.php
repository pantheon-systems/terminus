<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand.
 *
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
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function add($site_env, $domain)
    {
        $env = $this->getEnv($site_env);
        $env->getDomains()->create($domain);

        $this->log()->notice(
            'Added {domain} to {site}.{env}',
            [
                'domain' => $domain,
                'site' => $this->getSite($site_env)->getName(),
                'env' => $env->getName(),
            ]
        );
    }
}
