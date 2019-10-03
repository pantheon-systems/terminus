<?php

namespace Pantheon\Terminus\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\DataStore\FileStore;

/**
 * Class LookupCommand
 * @package Pantheon\Terminus\Commands\Domain
 */
class LookupCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays site and environment with which a given domain is associated.
     * Note: Only sites for which the user is authorized will appear.
     *
     * @authorize
     *
     * @command domain:lookup
     *
     * @option rebuild Force the domain cache to be rebuilt before doing lookup
     *
     * @field-labels
     *     site_id: Site ID
     *     site_name: Site Name
     *     env_id: Environment ID
     * @return PropertyList
     *
     * @param string $domain Domain e.g. `example.com`
     *
     * @throws TerminusNotFoundException
     *
     * @usage <domain_name> Returns the site and environment associated with <domain_name> or displays not found.
     * @usage <domain_name> --rebuild Rebuilds the cache and returns the site and environment associated with <domain_name> or displays not found.
     */
    public function lookup($domain, $options = ['rebuild' => false])
    {
        $this->log()->notice('This operation may take a long time to run.');
        $env = $this->checkCache($domain, $options['rebuild']);
        if (!isset($env)) {
            throw new TerminusNotFoundException(
                'Could not locate an environment with the domain {domain}.',
                compact('domain')
            );
        }
        return new PropertyList($env);
    }

    /**
     * Checks the cache for the domain and rebuilds it if not found.
     *
     * @param string  $domain  The domain to search for.
     * @param boolean $rebuild Force the cache to be rebuilt.
     *
     * @return array
     */
    private function checkCache($domain, $rebuild)
    {
        $domain_cache = $this->getCache();
        $domain_data = $domain_cache['domain_data'];

        if ($rebuild
            || !isset($domain_cache)
            || !isset($domain_data)
            || !isset($domain_data[$domain])) {
                $this->log()->notice('Rebuilding cache...');
                $domain_cache = $this->buildCache();
                $this->setCache($domain_cache);
                $domain_data = $domain_cache['domain_data'];
        }

        return $domain_data[$domain] ?? null;
    }

    /**
     * Reads the domain cache from disk.
     *
     * @return array
     */
    private function getCache()
    {
        $file_store = new FileStore($this->getConfig()->get('cache_dir'));
        $domain_cache = $file_store->get("domains");

        return $domain_cache;
    }

    /**
     * Writes the chache to disk.
     *
     * @param array $domain_cache The cache array.
     */
    private function setCache($domain_cache)
    {
        $file_store = new FileStore($this->getConfig()->get('cache_dir'));
        $file_store->set("domains", $domain_cache);
    }

    /**
     * Builds the cache by getting domain information from Pantheon.
     *
     * @return array
     */
    private function buildCache()
    {
        $domain_cache = array('domain_data' => array());
        $environments = ['dev', 'test', 'live',];
        $sites = $this->sites()->all();

        foreach ($sites as $site) {
            foreach ($environments as $env_name) {
                $domain_list = array_keys(
                    $site->getEnvironments()
                        ->get($env_name)
                        ->getDomains()
                        ->all()
                );

                foreach ($domain_list as $domain) {
                    $domain_cache['domain_data'][$domain]
                        = array("site_id" => $site->id,
                                "site_name" => $site->get('name'),
                                "env_id" => $env_name
                              );
                }
            }
        }

        return $domain_cache;
    }
}
