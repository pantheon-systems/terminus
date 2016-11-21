<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Environment;
use Terminus\Exceptions\TerminusNotFoundException;

class Hostnames extends EnvironmentOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Hostname';

    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/hostnames';

    /**
     * @var mixed Use to hydrate the data with additional information
     */
    protected $hydrate = false;

    /**
     * Adds a hostname to the environment
     *
     * @param string $hostname Hostname to add to environment
     * @return array
     */
    public function create($hostname)
    {
        $url = $this->replaceUrlTokens('sites/{site_id}/environments/{environment_id}/hostnames/');
        $url .= rawurlencode($hostname);
        $this->request->request($url, ['method' => 'put',]);
    }

    /**
     * Changes the value of the hydration property
     *
     * @param mixed $value Value to set the hydration property to
     * @return Hostnames
     */
    public function setHydration($value)
    {
        $this->hydrate = $value;
        return $this;
    }

    public function getUrl()
    {
        return parent::getUrl() . '?hydrate=' . $this->hydrate;
    }

    /**
     * Does the hostnames collection contain the given domain.
     *
     * @param $domain
     * @return bool True if the domain exists in the collection.
     */
    public function has($domain) {
        try {
            $this->get($domain);
            return true;
        }
        catch (TerminusNotFoundException $e) {
            return false;
        }
    }
}
