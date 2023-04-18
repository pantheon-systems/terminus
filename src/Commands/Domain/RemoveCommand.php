<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand.
 *
 * @package Pantheon\Terminus\Commands\Domain
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disassociates a domain from the environment.
     *
     * @authorize
     *
     * @command domain:remove
     * @aliases domain:rm
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain Domain e.g. `example.com`
     *
     * @usage <site>.<env> <domain_name> Disassociates <domain_name> from <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function remove($site_env, $domain)
    {
        $env = $this->getEnv($site_env);
        $env->getDomains()->get($domain)->delete();

        $this->log()->notice(
            'Removed {domain} from {site}.{env}',
            [
                'domain' => $domain,
                'site' => $this->fetchSite($site_env)->getName(),
                'env' => $env->getName(),
            ]
        );
    }
}
