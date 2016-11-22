<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Environment;
use Terminus\Exceptions\TerminusNotFoundException;

class Hostnames extends TerminusCollection
{
    /**
     * @var Environment
     */
    public $environment;
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Hostname';
    /**
     * @var mixed Use to hydrate the data with additional information
     */
    protected $hydrate = false;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->environment = $options['environment'];
    }

    /**
     * Adds a hostname to the environment
     *
     * @param string $hostname Hostname to add to environment
     * @return array
     */
    public function create($hostname)
    {
        $url = sprintf(
            'sites/%s/environments/%s/hostnames/%s',
            $this->environment->site->id,
            $this->environment->id,
            rawurlencode($hostname)
        );
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
        return sprintf(
            'sites/%s/environments/%s/hostnames?hydrate=%s',
            $this->environment->site->id,
            $this->environment->id,
            $this->hydrate
        );
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
