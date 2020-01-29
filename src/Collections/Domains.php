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
    const PRETTY_NAME = 'domains';
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
        $url = $this->getUrl() . '/' . rawurlencode($domain);
        $this->request->request($url, ['method' => 'put',]);
    }

    /**
     * Fetches domain data hydrated with recommendations
     *
     * @param array $options Additional information for the request
     * @return void
     */
    public function fetchWithRecommendations($options = [])
    {
        $this->setFetchArgs(['query' => ['hydrate' => ['as_list', 'recommendations',],],]);
        return $this->fetch();
    }
}
