<?php

namespace Pantheon\Terminus\PublicApi;

/**
 * Walks Relay-style connections from the Public API.
 *
 * v1 list responses look like:
 *   {"edges": [{"cursor": "...", "node": {...}}], "pageInfo": {"endCursor": "...", "hasNextPage": true}}
 *
 * The page-size and cursor parameter names vary by route, so callers say which to use:
 *   - most lists:            first / after (e.g. /sites/{id}/workflows, /sites/{id}/users)
 *   - nested connections:    e.g. workspace_sitesConnection_first / _after on /workspaces/{id},
 *                            with the connection under ['sitesConnection'] in the response
 *   - /sites/{id}/environments takes a `pager` object instead; it returns every environment in
 *     one page, so use get() and nodes() for it rather than walking.
 */
class Pager
{
    public const DEFAULT_PAGE_SIZE = 100;

    /**
     * Upper bound on pages fetched, so a misbehaving cursor can never loop forever.
     */
    public const MAX_PAGES = 1000;

    private Client $client;

    private int $pageSize;

    public function __construct(Client $client, int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->client = $client;
        $this->pageSize = max(1, $pageSize);
    }

    /**
     * Fetches every page and returns all nodes, in order.
     *
     * @param string $path Route path (see Client::path()).
     * @param array $query Extra query parameters.
     * @param string $firstParam Name of the page-size query parameter.
     * @param string $afterParam Name of the cursor query parameter.
     * @param string[] $connectionPath Keys leading from the response root to the connection.
     *
     * @return array[] The nodes.
     * @throws PublicApiException
     */
    public function all(
        string $path,
        array $query = [],
        string $firstParam = 'first',
        string $afterParam = 'after',
        array $connectionPath = []
    ): array {
        $nodes = [];
        $after = null;
        $seenCursors = [];
        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $pageQuery = $query + [$firstParam => $this->pageSize];
            if ($after !== null) {
                $pageQuery[$afterParam] = $after;
            }
            $connection = self::connectionAt($this->client->get($path, $pageQuery), $connectionPath, $path);
            array_push($nodes, ...self::nodes($connection));

            $pageInfo = $connection['pageInfo'] ?? [];
            $endCursor = $pageInfo['endCursor'] ?? null;
            // Stop when there is no next page, and also when the API claims a next page but gives no
            // usable cursor (seen live: GET /sites/{id}/users?first=1 returned no edges, hasNextPage
            // true and endCursor null), or repeats a cursor.
            if (
                empty($pageInfo['hasNextPage'])
                || !is_string($endCursor) || $endCursor === ''
                || isset($seenCursors[$endCursor])
            ) {
                return $nodes;
            }
            $seenCursors[$endCursor] = true;
            $after = $endCursor;
        }

        throw new PublicApiException(sprintf(
            'Stopped paging %s after %d pages; the API kept reporting more results.',
            $path,
            self::MAX_PAGES
        ));
    }

    /**
     * Returns the nodes of one connection.
     *
     * @return array[]
     */
    public static function nodes(array $connection): array
    {
        $nodes = [];
        foreach ($connection['edges'] ?? [] as $edge) {
            if (isset($edge['node']) && is_array($edge['node'])) {
                $nodes[] = $edge['node'];
            }
        }
        return $nodes;
    }

    /**
     * @param mixed $response
     * @throws PublicApiException
     */
    private static function connectionAt($response, array $connectionPath, string $path): array
    {
        $connection = $response;
        foreach ($connectionPath as $key) {
            $connection = is_array($connection) ? ($connection[$key] ?? null) : null;
        }
        if (!is_array($connection) || !array_key_exists('edges', $connection)) {
            throw new PublicApiException(sprintf(
                'Unexpected response from %s: expected a list with "edges".',
                $path
            ));
        }
        return $connection;
    }
}
