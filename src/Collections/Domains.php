<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Domain;

/**
 * Class Domains
 * @package Pantheon\Terminus\Collections
 */
class Domains extends EnvironmentOwnedCollection
{
    public static $pretty_name = 'domains';
    /**
     * @var string
     */
    protected $collected_class = Domain::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/domains';

    /**
     * Adds a domain to the environment
     *
     * @param string $domain Domain to add to environment
     * @return array
     */
    public function create($domain)
    {
        $url = $this->replaceUrlTokens("{$this->url}/") . rawurlencode($domain);
        $this->request->request($url, ['method' => 'put',]);
    }

    /**
     * Does the Domains collection contain the given domain?
     *
     * @param $domain
     * @return bool True if the domain exists in the collection.
     */
    public function has($domain)
    {
        try {
            $this->get($domain);
            return true;
        } catch (TerminusNotFoundException $e) {
            return false;
        }
    }
}
