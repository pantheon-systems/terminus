<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Loadbalancer;

/**
 * Class Loadbalancers
 * @package Pantheon\Terminus\Collections
 */
class Loadbalancers extends EnvironmentOwnedCollection
{
    public static $pretty_name = 'loadbalancers';
    /**
     * @var string
     */
    protected $collected_class = Loadbalancer::class;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->getEnvironment()->getUrl() . '?environment_state=true';
    }

    /**
     * Retrieves collection data from the API
     *
     * @param array $options params to pass to url request
     * @return array
     */
    protected function getCollectionData($options = [])
    {
        $env_id = $this->getEnvironment()->id;
        $data = parent::getCollectionData($options)->loadbalancers;
        $loadbalancers = array_filter(
            (array)$data,
            function ($loadbalancer) use ($env_id) {
                return $loadbalancer->environment == $env_id;
            }
        );
        return $loadbalancers;
    }
}
