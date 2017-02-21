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
     * @var mixed Use to hydrate the data with additional information
     */
    protected $hydrate = false;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/hostnames';

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
     * @return string
     */
    public function getUrl()
    {
        return parent::getUrl() . '?hydrate=' . $this->hydrate;
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

    /**
     * Changes the value of the hydration property
     *
     * @param mixed $value Value to set the hydration property to
     * @return Domains
     */
    public function setHydration($value)
    {
        $this->hydrate = $value;
        return $this;
    }
}
