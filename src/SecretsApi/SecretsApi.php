<?php

namespace Pantheon\Terminus\SecretsApi;

class SecretsApi {

    /**
     * List secrets for a given site.
     *
     * @param string $site_id
     *   Site id to get secrets for.
     *
     * @return array
     *   Secrets for given site.
     */
    public function listSecrets($site_id): array
    {
        return [
            [
                'name' => 'foo',
                'value' => 'bar',
            ],
        ];
    }
    
}