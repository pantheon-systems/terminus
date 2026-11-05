<?php

namespace Pantheon\Terminus\Commands\Search;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Search
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;
    use SearchFlavorTrait;

    /**
     * Disables search indexing add-on for a site.
     *
     * @authorize
     * @interact
     *
     * @command search:disable
     *
     * @param string $site_id Site name
     * @param array $options
     * @option string $flavor [solr|elasticsearch] Search engine flavor to disable. Defaults to 'solr' for Drupal sites and 'elasticsearch' for WordPress sites.
     *
     * @usage <site> Disables search indexing for <site> (auto-detects flavor).
     * @usage <site> --flavor=solr Disables Solr indexing for <site>.
     * @usage <site> --flavor=elasticsearch Disables Elasticsearch for <site>.
     *
     * @throws TerminusException
     */
    public function disable($site_id, array $options = ['flavor' => null])
    {
        $site = $this->getSiteById($site_id);
        $framework = $site->getFramework();

        // Determine flavor
        $flavor = $this->determineFlavor($options['flavor'], $framework);

        // Disable the appropriate search engine
        if ($flavor === 'elasticsearch') {
            $workflow = $site->getElasticsearch()->disable();
            $this->log()->notice('Disabling Elasticsearch for {site}...', ['site' => $site_id]);
        } else {
            $workflow = $site->getSolr()->disable();
            $this->log()->notice('Disabling Solr for {site}...', ['site' => $site_id]);
        }

        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
