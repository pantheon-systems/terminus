<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Deletes a domain from the environment
     *
     * @command domain:remove
     *
     * @param string $site_env Site & environment to remove a domain from, in the form `site-name.env`.
     * @param string $domain   The domain to disassociate from this environment
     *
     * @usage terminus domain:delete <site_name>.<env_id> <domain_name>
     *     Removes <domain_name> from <site name> site's <env_id> environment
     */
    public function remove($site_env, $domain)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $env->hostnames->get($domain)->delete();
        $this->log()->notice(
            'Removed {domain} from {site}.{env}',
            ['domain' => $domain, 'site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
