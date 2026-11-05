<?php

namespace Pantheon\Terminus\Commands\Search;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Utility\SiteFramework;

/**
 * Trait SearchFlavorTrait
 * @package Pantheon\Terminus\Commands\Search
 */
trait SearchFlavorTrait
{
    /**
     * Determines the search flavor to use
     *
     * @param string|null $requested_flavor The flavor requested by the user
     * @param SiteFramework $framework The site framework
     * @return string The flavor to use ('solr' or 'elasticsearch')
     * @throws TerminusException
     */
    private function determineFlavor($requested_flavor, SiteFramework $framework)
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
}
