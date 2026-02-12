<?php

namespace Pantheon\Terminus\Commands\Search;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Search
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Enables search indexing add-on for a site.
     *
     * @authorize
     * @interact
     *
     * @command search:enable
     *
     * @param string $site_id Site name
     * @param array $options
     * @option string $flavor [solr|elasticsearch] Search engine flavor to enable. Defaults to 'solr' for Drupal sites and 'elasticsearch' for WordPress sites.
     *
     * @usage <site> Enables search indexing for <site> (auto-detects flavor).
     * @usage <site> --flavor=solr Enables Solr indexing for <site>.
     * @usage <site> --flavor=elasticsearch Enables Elasticsearch for <site>.
     *
     * @throws TerminusException
     */
    public function enable($site_id, array $options = ['flavor' => null])
    {
        $site = $this->getSiteById($site_id);
        $framework = $site->getFramework();

        // Determine flavor
        $flavor = $this->determineFlavor($options['flavor'], $framework);

        // Validate flavor for framework
        $this->validateFlavorForFramework($flavor, $framework, $site);

        // Enable the appropriate search engine
        if ($flavor === 'elasticsearch') {
            $workflow = $site->getElasticsearch()->enable();
            $this->log()->notice('Enabling Elasticsearch for {site}...', ['site' => $site_id]);
        } else {
            $workflow = $site->getSolr()->enable();
            $this->log()->notice('Enabling Solr for {site}...', ['site' => $site_id]);
        }

        $this->processWorkflow($workflow);

        // Display custom success message with proper capitalization
        if ($workflow->isSuccessful()) {
            if ($flavor === 'elasticsearch') {
                $this->log()->notice('Enabled Elasticsearch for {site}.', ['site' => $site_id]);
                $this->log()->notice(
                    'Read the documentation to complete the Elasticsearch configuration for your site: ' .
                    'https://docs.pantheon.io/pantheon-search'
                );
            } else {
                $this->log()->notice('Enabled Solr for {site}.', ['site' => $site_id]);
            }
        } else {
            // If workflow failed, show the API error message
            $this->log()->notice($workflow->getMessage());
        }
    }

    /**
     * Determines the search flavor to use
     *
     * @param string|null $requested_flavor The flavor requested by the user
     * @param \Pantheon\Terminus\Helpers\Utility\SiteFramework $framework The site framework
     * @return string The flavor to use ('solr' or 'elastic')
     */
    private function determineFlavor($requested_flavor, $framework)
    {
        if ($requested_flavor !== null) {
            $flavor = strtolower($requested_flavor);
            if (!in_array($flavor, ['solr', 'elasticsearch'])) {
                throw new TerminusException(
                    'Invalid flavor "{flavor}". Must be either "solr" or "elasticsearch".',
                    ['flavor' => $requested_flavor]
                );
            }
            return $flavor;
        }

        // Auto-detect based on framework
        if ($framework->isWordpressFramework()) {
            return 'elasticsearch';
        }

        return 'solr'; // Default for Drupal and others
    }

    /**
     * Validates that the flavor is compatible with the framework
     *
     * @param string $flavor The search flavor
     * @param \Pantheon\Terminus\Helpers\Utility\SiteFramework $framework The site framework
     * @param \Pantheon\Terminus\Models\Site $site The site model
     * @throws TerminusException
     */
    private function validateFlavorForFramework($flavor, $framework, $site)
    {
        // Block Drupal sites from using Elasticsearch
        if ($flavor === 'elasticsearch' &&
            ($framework->isDrupal7Framework() || $framework->isDrupal8Framework())) {
            throw new TerminusException(
                'Elasticsearch is not supported for Drupal sites. Please use Solr instead.'
            );
        }

        // WordPress sites: Let the API workflow validate Elasticsearch entitlement
        // The workflow will fail with an appropriate error if not entitled
    }
}
