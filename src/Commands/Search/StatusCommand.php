<?php

namespace Pantheon\Terminus\Commands\Search;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\Search
 */
class StatusCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the status of search indexing services for a site.
     *
     * @authorize
     *
     * @command search:status
     *
     * @param string $site_id Site name
     *
     * @usage <site> Displays search service status for <site>.
     */
    public function status($site_id)
    {
        $site = $this->getSiteById($site_id);
        $framework = $site->getFramework();
        $settings = $site->get('settings');

        $solrEnabled = !empty($settings->allow_indexserver);
        $this->log()->notice('Solr: {status}', [
            'status' => $solrEnabled ? 'enabled' : 'disabled',
        ]);

        if ($framework->isWordpressFramework()) {
            $esEnabled = !empty($settings->allow_elasticsearch);
            $this->log()->notice('Elasticsearch: {status}', [
                'status' => $esEnabled ? 'enabled' : 'disabled',
            ]);
        }
    }
}
