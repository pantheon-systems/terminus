<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusNotFoundException;

class LookupCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Looks up which environment a given domain is associated with
     *
     * @command domain:lookup
     *
     * @param string $domain The domain to search your site environments for
     * @return PropertyList
     * @throws TerminusNotFoundException
     *
     * @field-labels
     *   site_id: Site ID
     *   site_name: Site Name
     *   env_id: Environment ID
     *
     * @usage terminus domain:lookup <domain_name>
     *    * If found, will return information about the environment with which this domain is associated
     *    * If not found, will throw a TerminusNotFound exception
     */
    public function lookup($domain)
    {
        $this->log()->notice('This operation may take a long time to run.');
        $sites = $this->sites()->fetch()->all();
        $environments = ['dev', 'test', 'live',];
        foreach ($sites as $site_id => $site) {
            foreach ($environments as $env_name) {
                if (in_array($domain, $site->getEnvironments()->get($env_name)->getHostnames()->fetch()->ids())) {
                    $env = ['site_id' => $site->id, 'site_name' => $site->get('name'), 'env_id' => $env_name,];
                    break 2;
                }
            }
        }
        if (!isset($env)) {
            throw new TerminusNotFoundException(
                'Could not locate an environment with the domain {domain}.',
                compact('domain')
            );
        }
        return new PropertyList($env);
    }
}
