<?php

namespace Pantheon\Terminus\SecretsApi;

use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Request\RequestOperationResult;

/**
 * Temporary Secrets API client until formal PantheonAPI client is available.
 */
class SecretsApi
{
    use RequestAwareTrait;

    /**
     * Used only for testing purposes. May be removed later.
     */
    protected $secrets = [];

    /**
     * Parses the base URI for requests.
     *
     * @return string
     */
    private function getBaseURI()
    {
        $config = $this->request()->getConfig();

        $protocol = $config->get('papi_protocol') ?? $config->get('protocol');
        $port = $config->get('papi_port') ?? $config->get('port');
        $host = $config->get('papi_host');
        if (!$host && strpos($config->get('host'), 'hermes.sandbox-') !== false) {
            $host = str_replace('hermes', 'pantheonapi', $config->get('host'));
        }
        if (!$host && strpos($config->get('host'), 'sandbox-') !== false) {
            $host = $config->get('host');
        }
        // If host is still not set, use the default host.
        if (!$host) {
            $host = 'terminus.pantheon.io';
        }

        return sprintf(
            '%s://%s:%s/customer-secrets/v1',
            $protocol,
            $host,
            $port
        );
    }

    /**
     * Fetch secrets for a given site.
     *
     * @param string $workspaceId
     *   Site/org id to get secrets for.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     * @param string $workspaceType
     *   Whether to return the secrets for a site or org.
     *
     * @return Pantheon\Terminus\Request\RequestOperationResult
     *   Request operation result.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function fetchSecrets(string $workspaceId, bool $debug = false, string $workspaceType = "sites")
    {
        if (getenv('TERMINUS_PLUGIN_TESTING_MODE')) {
            if (file_exists('/tmp/secrets.json')) {
                $this->secrets = json_decode(file_get_contents('/tmp/secrets.json'), true);
            }
            return array_values($this->secrets);
        }

        $url = sprintf(
            '%s/%s/%s/secrets%s',
            $this->getBaseURI(),
            $workspaceType,
            $workspaceId,
            $workspaceType == "sites" ? "/showall" : ""
        );
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $this->request()->session()->get('session'),
            ],
            'debug' => $debug,
        ];
        return $this->request()->request($url, $options);
    }

    /**
     * List secrets for a given site.
     *
     * @param string $workspaceId
     *   Site/org id to get secrets for.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     * @param string $workspaceType
     *   Whether to return the secrets for a site or org.
     *
     * @return array|Pantheon\Terminus\Request\RequestOperationResult
     *   Secrets for given site or the operation result in case of error.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listSecrets(string $workspaceId, bool $debug = false, string $workspaceType = "sites")
    {
        $result = $this->fetchSecrets($workspaceId, $debug, $workspaceType);
        if ($result->getStatusCode() !== 200) {
            return $result;
        }
        $data = $result->getData();
        $secrets = [];
        foreach ($data->Secrets ?? [] as $secretKey => $secretValue) {
            // Key the rows of fields entries by their secret keys
            $secrets[$secretKey] = [
                'name' => $secretKey,
                'type' => $secretValue->Type,
                'value' => $secretValue->Value ?? null,
                'scopes' => $secretValue->Scopes,
                'env-values' => (array) ($secretValue->EnvValues ?? []),
            ];
            if ($workspaceType === "sites") {
                $secrets[$secretKey]["org-values"] = (array) ($secretValue->OrgValues ?? []);
            }
        }
        return $secrets;
    }

    /**
     * Set secret for a given site.
     *
     * @param string $workspaceId
     *   Site/Org id to set secret for.
     * @param string $name
     *   Secret name.
     * @param string $value
     *   Secret value.
     * @param string $env_name
     *  Environment to set secret for.
     * @param string $type
     *   Secret type.
     * @param string $scopes
     *   Secret scopes.
     * @param bool $debug
     *   Whether to return the secrets in debug mode.
     * @param string $workspaceType
     *   Whether to return the secrets for a site or org.
     *
     * @return Pantheon\Terminus\Request\RequestOperationResult
     *   Result of the request operation.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function setSecret(
        string $workspaceId,
        string $name,
        string $value,
        ?string $env_name = null,
        ?string $type = null,
        ?string $scopes = null,
        bool $debug = false,
        string $workspaceType = "sites"
    ): RequestOperationResult {
        if (getenv('TERMINUS_PLUGIN_TESTING_MODE')) {
            if (file_exists('/tmp/secrets.json')) {
                $this->secrets = json_decode(file_get_contents('/tmp/secrets.json'), true);
            }
            $this->secrets = [
                'name' => $name,
                'value' => $value,
                'env' => $env_name,
            ];
            file_put_contents('/tmp/secrets.json', json_encode($this->secrets));
            return true;
        }
        $url = sprintf('%s/%s/%s/secrets', $this->getBaseURI(), $workspaceType, $workspaceId);
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $this->request()->session()->get('session'),
            ],
            'method' => 'POST',
            'debug' => $debug,
        ];
        $body = [
            'name' => $name,
            'value' => $value,
        ];
        if ($type) {
            $body['type'] = $type;
        }
        if ($scopes) {
            $scopes = array_map('trim', explode(',', $scopes));
            $body['scopes'] = $scopes;
        }

        if ($env_name) {
            $url = sprintf('%s/%s/%s/secrets/%s', $this->getBaseURI(), $workspaceType, $workspaceId, $name);
            $body['env'] = $env_name;
            $options['method'] = 'PATCH';

            // else statements are bad, so we unset rather than reversing previous logic
            unset($body['name']);
            unset($body['type']);
            unset($body['scopes']);
        }
        $options['json'] = $body;

        $result = $this->request()->request($url, $options);

        // If code is 400 and data contains "PATCH", the secret exists; re-send the request as patch.
        if ($result->getStatusCode() == 400 && strpos($result->getData(), 'PATCH') !== false) {
            if (empty($body['type']) && empty($body['scopes'])) {
                // PATCH can only be sent with empty type and scopes.
                $options['method'] = 'PATCH';
                $url = sprintf('%s/%s/%s/secrets/%s', $this->getBaseURI(), $workspaceType, $workspaceId, $name);
                unset($options['json']['name']);
                $result = $this->request()->request($url, $options);
            }
            else {
                $result->setData(sprintf("Secret '%s' already exists. To update the value, omit type and scopes options.", $name));
            }
        }
        return $result;
    }

    /**
     * @param string $workspaceId
     * @param string $name
     * @param string $env
     * @param bool $debug
     * @param string $workspaceType
     *
     * @return Pantheon\Terminus\Request\RequestOperationResult
     *   Result of the request operation.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function deleteSecret(
        string $workspaceId,
        string $name,
        ?string $env = null,
        bool $debug = false,
        string $workspaceType = "sites"
    ): RequestOperationResult {
        if (getenv('TERMINUS_PLUGIN_TESTING_MODE')) {
            if (file_exists('/tmp/secrets.json')) {
                $this->secrets = json_decode(file_get_contents('/tmp/secrets.json'), true);
            }
            if (isset($this->secrets[$name])) {
                unset($this->secrets[$name]);
                file_put_contents('/tmp/secrets.json', json_encode($this->secrets));
            }
            return true;
        }

        $url = sprintf('%s/%s/%s/secrets/%s', $this->getBaseURI(), $workspaceType, $workspaceId, $name);
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $this->request()->session()->get('session'),
            ],
            'method' => 'DELETE',
            'debug' => $debug,
        ];

        if ($env) {
            $options['method'] = 'PATCH';

            // null value deletes the secret for the given env.
            $options['json'] = ['env' => $env, 'value' => null];
        }
        return $this->request()->request($url, $options);
    }
}
