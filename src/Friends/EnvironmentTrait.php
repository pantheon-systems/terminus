<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Environment;

/**
 * Class EnvironmentTrait
 * @package Pantheon\Terminus\Friends
 */
trait EnvironmentTrait
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @return Environment Returns a Environment-type object
     */
    public function getEnvironment()
    {
        if (empty($this->environment) && isset($this->collection)) {
            $this->setEnvironment($this->collection->getEnvironment());
        }
        return $this->environment;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }
}
