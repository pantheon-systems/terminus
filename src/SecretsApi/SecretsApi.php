<?php

namespace Pantheon\Terminus\SecretsApi;

class SecretsApi {

    /**
     * List secrets for a given site.
     *
     * @param string $site_id
     *   Site id to get secrets for.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     *
     * @return array
     *   Secrets for given site.
     */
    public function listSecrets(string $site_id, bool $debug = false): array
    {
        return [
            [
                'name' => 'foo',
                'value' => 'bar',
            ],
        ];
    }
    
}