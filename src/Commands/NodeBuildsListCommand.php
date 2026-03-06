<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Commands\Import\SiteCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Build\BuildAwareTrait;

/**
 * Fetch the list of builds for a site.
 */
class NodeBuildsListCommand extends SiteCommand implements SiteAwareInterface, RequestAwareInterface
{
    use BuildAwareTrait;
    use SiteAwareTrait;
    use RequestAwareTrait;
    use StructuredListTrait;

    /**
     * Print the list of builds to the log.
     *
     * @authorize
     * @filter-output
     *
     * @command node:builds:list
     * @aliases nbl,nlbl,node:logs:build:list
     *
     * @field-labels
     *   id: ID
     *   status: Status
     *   branch: Branch/Tag
     *   commit: Commit
     *   created: Created
     *   completed: Completed
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     * @option string $status Filter by build status. Valid values: SUCCESS, FAILURE, WORKING, TIMEOUT.
     * @option string $branch Filter by branch or tag name.
     * @option string $limit Limit the number of builds returned.
     */
    public function buildList($site_env, $options = [
        "status" => null,
        "limit" => 10,
        "branch" => null,
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);

        $site = $this->getSiteById($site_env);
        $env = explode('.', $site_env)[1];

        if ($env == "") {
            $this->log()->error('Please provide a valid environment to list the builds.');
            return;
        }

        $builds = $this->getFromUrl(
            sprintf('/api/sites/%s/environment/%s/build/list?%s', $site->id, $env, http_build_query([
                    'status' => $options['status'],
                    'limit' => $options['limit'],
                    'branch' => $options['branch'],
            ]))
        );

        if (empty($builds)) {
            $this->log()->notice(sprintf('No builds found for the "%s" environment.', $env));
            return;
        }

        $this->builds()->fetch($builds);
        return $this->getRowsOfFields($this->builds);
    }

    /**
     * Get data from a given url.
     *
     * @param string $url Url to get data from.
     *
     * @return array|string|null
     */
    private function getFromUrl(string $url)
    {
        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $url = sprintf('%s://%s%s', $protocol, $host, $url);

        $options = [
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];
        $result = $this->request()->request($url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            return null;
        }

        $data = $result->getData();
        if (empty($data)) {
            return null;
        }

        return $data;
    }
}
