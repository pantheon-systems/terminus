<?php

namespace Pantheon\Terminus\Tests\Traits;

use GuzzleHttp\Client;

/**
 * Trait TerminusTestTrait
 *
 * @package Pantheon\Terminus\Tests\Traits
 */
trait UrlStatusCodeHelperTrait {

    protected Client $client;

    public function getStatusCodeForUrl($url) : int
    {
        if (!isset($this->client)) {
            $this->client = new Client([
                "debug" => false,
                "http_errors" => false,
            ]);
        }
        $response = $this->client->get($url);
        return $response->getStatusCode();
    }

}
