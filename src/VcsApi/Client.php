<?php

namespace Pantheon\Terminus\VcsApi;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Vcs Auth API Client.
 */
class Client implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @var \Pantheon\Terminus\Request\Request
     */
    protected Request $request;

    /**
     * @var int
     */
    protected int $pollingInterval;

    /**
     * Constructor.
     *
     * @param \Pantheon\Terminus\Request\Request $request
     * @param int $polling_interval
     */
    public function __construct(Request $request, int $polling_interval)
    {
        $this->request = $request;
        $this->pollingInterval = $polling_interval;
    }

    /**
     * Call installwithtoken endpoint.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function installWithToken(array $data): array
    {
        $request_options = [
            'json' => $data,
            'method' => 'POST',
        ];

        return $this->requestApi('installwithtoken', $request_options, "X-Pantheon-Session");
    }

    /**
     * Get site details by id.
     */
    public function getSiteDetails(string $site_id): array
    {
        $request_options = [
            'method' => 'GET',
        ];

        return $this->requestApi('site-details/' . $site_id, $request_options);
    }





    /**
     * Pause build for a given site.
     *
     * @param string $site_id
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function pauseBuild(string $site_id): array
    {
        $request_options = [
            'method' => 'POST',
        ];

        return $this->requestApi('site-details/' . $site_id . '/pause-builds', $request_options, "X-Pantheon-Session");
    }

    /**
     * Resume build for a given site.
     *
     * @param string $site_id
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function resumeBuild(string $site_id): array
    {
        $request_options = [
            'method' => 'POST',
        ];

        return $this->requestApi('site-details/' . $site_id . '/resume-builds', $request_options, "X-Pantheon-Session");
    }

    /**
     * Rebuild code for a given site environment.
     *
     * @param string $site_id
     * @param string $env
     * @param string|null $commit_id
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function rebuild(string $site_id, string $env, ?string $commit_id = null): array
    {
        $json_body = new \stdClass();

        if ($commit_id !== null) {
            $json_body->commit = $commit_id;
        }

        $request_options = [
            'method' => 'POST',
            'json' => $json_body,
        ];

        return $this->requestApi(
            sprintf('site-details/%s/environments/%s/rebuild', $site_id, $env),
            $request_options,
            "X-Pantheon-Session"
        );
    }

    /**
     * Get site details by id.
     */
    public function getSiteDetailsById(string $site_id): array
    {
        $request_options = [
            'method' => 'GET',
        ];

        return $this->requestApi('site-details/' . $site_id, $request_options);
    }

    /**
     * Pushes GitHub VCS event to the VCS API.
     */
    public function githubVcs($data, string $site_id, string $event_name): array
    {
        $request_options = [
            'json' => $data,
            'method' => 'POST',
            'headers' => [
                'X-Pantheon-Site-Id' => $site_id,
                'Accept' => 'application/json',
                'X-Pantheon-Session' => $this->request->session()->get('session'),
                'X-GitHub-Event' => $event_name,
            ],
        ];

        return $this->requestApi('github-vcs', $request_options, "X-Pantheon-Session");
    }

    /**
     * Get existing installations.
     */
    public function getInstallations(string $org_id, string $user_id): array
    {
        $request_options = [
            'method' => 'GET',
        ];

        return $this->requestApi(
            sprintf('installation/user/%s/org/%s', $user_id, $org_id),
            $request_options,
            "X-Pantheon-Session"
        );
    }

    /**
     * Link an existing VCS installation to a new Pantheon organization.
     *
     * @param string $source_org_id Source Pantheon organization UUID
     * @param string $destination_org_id Destination Pantheon organization UUID
     * @param string $installation_id VCS installation ID
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function linkInstallation(
        string $source_org_id,
        string $destination_org_id,
        string $installation_id
    ): array {
        $request_options = [
            'json' => [
                'organization_id' => $source_org_id,
                'installation_id' => $installation_id,
            ],
            'method' => 'POST',
        ];

        $path = sprintf('authorize/organization/%s', $destination_org_id);

        return $this->requestApi($path, $request_options, "X-Pantheon-Session");
    }

    /**
     * Get auth links.
     */
    public function getAuthLinks(string $org_uuid, string $user_uuid, string $site_type, string $callback_url, ?string $github_host = null): array
    {
        $json = [
            'user_uuid' => $user_uuid,
            'org_uuid' => $org_uuid,
            'site_type' => $site_type,
            'redirect_uri' => $callback_url,
        ];

        if ($github_host !== null) {
            $json['github_host'] = $github_host;
        }

        $request_options = [
            'method' => 'POST',
            'json' => $json,
        ];

        return $this->requestApi('installation/auth', $request_options, "X-Pantheon-Session");
    }

    /**
     * Search for repositories.
     */
    public function searchRepositories(string $repo_name, string $org_uuid, string $installation_id): array
    {
        $request_options = [
            'method' => 'GET',
        ];

        $path = sprintf(
            'repository/search?search=%s&org_id=%s&installation_id=%s',
            urlencode($repo_name),
            $org_uuid,
            $installation_id
        );

        return $this->requestApi($path, $request_options, "X-Pantheon-Session");
    }

    /**
     * Validate a repository name and check existence via the VCS service.
     */
    public function validateRepositoryName(
        string $repo_name,
        string $org_id,
        string $installation_id,
        bool $skip_create = false
    ): array {
        $request_options = [
            'method' => 'GET',
        ];

        $path = sprintf(
            'repository/validate-name?name=%s&org_id=%s&installation_id=%s&skip_create=%s',
            urlencode($repo_name),
            $org_id,
            $installation_id,
            $skip_create ? 'true' : 'false'
        );

        return $this->requestApi($path, $request_options, "X-Pantheon-Session");
    }

    /**
     * Get the GHES app manifest for provisioning.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getProvisionManifest(string $hostname): array
    {
        $request_options = [
            'method' => 'GET',
        ];

        $path = 'provision/manifest?hostname=' . urlencode($hostname);

        return $this->requestApi($path, $request_options, "X-Pantheon-Session");
    }

    /**
     * Provision a GHES instance with app credentials.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function provision(array $data): array
    {
        $request_options = [
            'method' => 'POST',
            'json' => $data,
        ];

        return $this->requestApi('provision', $request_options, "X-Pantheon-Session");
    }

    /**
     * Performs the request to API path.
     *
     * @param string $path
     * @param array $options
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function requestApi(string $path, array $options = [], string $auth_header_name = "Authorization"): array
    {
        $url = sprintf('%s/%s', $this->getPantheonApiBaseUri(), $path);
        $options = array_merge(
            [
                'headers' => [
                    'Accept' => 'application/json',
                    $auth_header_name => $this->request->session()->get('session'),
                ],
                // Do not convert http errors to exceptions
                'http_errors' => false,
            ],
            $options
        );

        $result = $this->request->request($url, $options);
        $statusCode = $result->getStatusCode();
        $data = $result->getData();
        // If it went ok, just return data.
        if ($statusCode >= 200 && $statusCode < 300) {
            return (array) $result->getData();
        }
        if (!empty($data->error)) {
            // If error was correctly set from backend, throw it.
            throw new TerminusException($data->error);
        }
        // Check for nested error format: {"errors":{"error":"message"}}
        if (!empty($data->errors->error)) {
            throw new TerminusException($data->errors->error);
        }
        throw new TerminusException(
            'An error ocurred. Code: {code}. Message: {reason}',
            [
                'code' => $statusCode,
                'reason' => $result->getStatusCodeReason(),
            ]
        );
    }

    /**
     * Returns Pantheon API base URI.
     *
     * @return string
     */
    public function getPantheonApiBaseUri(): string
    {
        $config = $this->request->getConfig();

        return sprintf(
            '%s://%s:%s/vcs/v1',
            $config->get('papi_protocol') ?? $config->get('protocol') ?? 'https',
            $this->getHost(),
            $config->get('papi_port') ?? $config->get('port') ?? '443'
        );
    }

    /**
     * Returns Pantheon API host.
     *
     * @return string
     */
    protected function getHost(): string
    {
        $config = $this->request->getConfig();

        if ($config->get('papi_host')) {
            return $config->get('papi_host');
        }

        if ($config->get('host') && false !== strpos($config->get('host'), 'hermes.sandbox-')) {
            return str_replace('hermes', 'pantheonapi', $config->get('host'));
        }
        if ($config->get('host') && strpos($config->get('host'), 'sandbox-') !== false) {
            return $config->get('host');
        }

        return 'terminus.pantheon.io';
    }
}
