<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Loadbalancer;

/**
 * Class Loadbalancers
 * @package Pantheon\Terminus\Collections
 */
class Loadbalancers extends EnvironmentOwnedCollection
{
    /**
     * @var Environment
     */
    public $environment;
    /**
     * @var string
     */
    protected $collected_class = Loadbalancer::class;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->environment->getUrl() . '?environment_state=true';
    }

    /**
     * Retrieves collection data from the API
     *
     * @param array $options params to pass to url request
     * @return array
     */
    protected function getCollectionData($options = [])
    {
        $env_id = $this->environment->id;
        $loadbalancers = array_filter(
            (array)parent::getCollectionData($options)->loadbalancers,
            function ($loadbalancer) use ($env_id) {
                return $loadbalancer->environment == $env_id;
            }
        );
        return $loadbalancers;
    }
}
