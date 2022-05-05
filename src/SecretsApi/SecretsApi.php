<?php

namespace Pantheon\Terminus\SecretsApi;

class SecretsApi
{

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

    /**
     * Set secret for a given site.
     *
     * @param string $site_id
     *   Site id to set secret for.
     * @param string $name
     *   Secret name.
     * @param string $value
     *   Secret value.
     * @param string $type
     *   Secret type.
     * @param array $scopes
     *   Secret scopes.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     *
     * @return bool
     *   Whether saving the secret was successful or not.
     */
    public function setSecret(
        string $site_id,
        string $name,
        string $value,
        string $type = 'variable',
        array $scopes = ['integrated-composer'],
        bool $debug = false
    ): bool {
        return true;
    }

    /**
     * Delete secret for a given site.
     *
     * @param string $site_id
     *   Site id to set secret for.
     * @param string $name
     *   Secret name.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     *
     * @return bool
     *   Whether saving the secret was successful or not.
     */
    public function deleteSecret(string $site_id, string $name, bool $debug = false): bool
    {
        return true;
    }
}
