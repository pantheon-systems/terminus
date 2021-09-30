<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Domain;
use Pantheon\Terminus\Request\RequestOperationResult;

/**
 * Class Domains.
 *
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
     * Adds a domain to the environment.
     *
     * @param string $domain
     *   Domain to add to environment.
     *
     * @return \Pantheon\Terminus\Request\RequestOperationResult
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($domain): RequestOperationResult
    {
        $url = $this->getUrl() . '/' . rawurlencode($domain);
        $result = $this->request->request($url, ['method' => 'put']);
        if ($result->isError()) {
            throw new TerminusException(
                'Error trying to add {domain} to {site}.{env}: {error}',
                [
                    'domain' => $domain,
                    'site' => $this->getEnvironment()->getSite()->getName(),
                    'env' => $this->getEnvironment()->getName(),
                    'error' => $result->getData(),
                ]
            );
        }

        return $result;
    }

    /**
     * Fetches domain data hydrated with recommendations.
     *
     * @return \Pantheon\Terminus\Collections\TerminusCollection
     */
    public function fetchWithRecommendations()
    {
        $this->setFetchArgs(['query' => ['hydrate' => ['as_list', 'recommendations']]]);

        return $this->fetch();
    }
}
