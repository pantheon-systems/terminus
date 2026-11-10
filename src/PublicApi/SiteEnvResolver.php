<?php

namespace Pantheon\Terminus\PublicApi;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Resolves site and `site.env` arguments against the Public API v1.
 *
 * This is the v1 counterpart of SiteAwareTrait::getEnv()/getSiteById(), deliberately separate from
 * the legacy Sites collection. A site name costs one extra call (GET /sites/by-name/{name} returns
 * only the ID); a UUID is used as-is.
 */
class SiteEnvResolver
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Splits `site.env` the way SiteAwareTrait::getEnv() does.
     *
     * @return string[] [site, env]
     * @throws TerminusException When no environment is given and there is no default.
     */
    public static function parse(string $siteEnv, ?string $defaultEnv = null): array
    {
        if ($defaultEnv === null && strpos($siteEnv, '.') === false) {
            throw new TerminusException('The environment argument must be given as <site_name>.<environment>');
        }
        $parts = explode('.', $siteEnv);
        return [$parts[0], $parts[1] ?? $defaultEnv];
    }

    /**
     * Resolves `site.env` to the site ID, site name and the environment.
     *
     * @throws TerminusException
     * @throws TerminusNotFoundException When the environment does not exist.
     */
    public function resolve(string $siteEnv, ?string $defaultEnv = null): SiteEnv
    {
        [$site, $envName] = self::parse($siteEnv, $defaultEnv);
        $siteId = $this->resolveSiteId($site);
        $siteName = self::isUuid($site) ? (string) ($this->getSite($siteId)['machineName'] ?? $site) : $site;
        return new SiteEnv($siteId, $siteName, $envName, $this->getEnvironment($siteId, $envName));
    }

    /**
     * Returns the site ID for a site name or UUID.
     *
     * @throws TerminusException When the site does not exist or the user cannot access it.
     */
    public function resolveSiteId(string $site): string
    {
        if (self::isUuid($site)) {
            return strtolower($site);
        }
        try {
            $id = $this->client->get(Client::path('/sites/by-name/%s', $site));
        } catch (PublicApiException $e) {
            // v1 answers 403 UNAUTHORIZED both for "no such site" and "no access".
            throw self::siteNotFound($site, $e);
        }
        if (!is_string($id) || $id === '') {
            throw self::siteNotFound($site, null);
        }
        return $id;
    }

    /**
     * GET /sites/{id}.
     *
     * @throws TerminusException
     */
    public function getSite(string $siteId): array
    {
        try {
            return (array) $this->client->get(Client::path('/sites/%s', $siteId));
        } catch (PublicApiException $e) {
            throw self::siteNotFound($siteId, $e);
        }
    }

    /**
     * All of a site's environments (v1 Environment nodes), keyed by environment ID.
     *
     * GET /sites/{id}/environments returns every environment in one page (it takes a `pager`
     * object rather than first/after, and live it ignored the page size), so this does not walk.
     *
     * @return array<string, array>
     * @throws PublicApiException
     */
    public function getEnvironments(string $siteId): array
    {
        $connection = $this->client->get(Client::path('/sites/%s/environments', $siteId));
        $environments = [];
        foreach (Pager::nodes(is_array($connection) ? $connection : []) as $node) {
            $environments[(string) ($node['id'] ?? $node['name'] ?? '')] = $node;
        }
        return $environments;
    }

    /**
     * @throws TerminusNotFoundException
     * @throws PublicApiException
     */
    public function getEnvironment(string $siteId, string $envName): array
    {
        $environments = $this->getEnvironments($siteId);
        if (!isset($environments[$envName])) {
            throw new TerminusNotFoundException(
                'Could not find an environment identified by {id}.',
                ['id' => $envName]
            );
        }
        return $environments[$envName];
    }

    public static function isUuid(string $value): bool
    {
        return (bool) preg_match(self::UUID_PATTERN, $value);
    }

    private static function siteNotFound(string $site, ?PublicApiException $e): TerminusException
    {
        return new TerminusException(
            'Could not locate a site your user may access identified by {id}: {error_message}',
            [
                'id' => $site,
                'error_message' => $e ? $e->getMessage() : 'the API returned no site ID',
            ]
        );
    }
}
