<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class InfoCommand extends SiteCommand
{
    /**
     * Displays a site's information.
     *
     * @authorize
     *
     * @command site:info
     *
     * @field-labels
     *     id: ID
     *     name: Name
     *     label: Label
     *     created: Created
     *     framework: Framework
     *     organization: Organization
     *     service_level: Service Level
     *     max_num_cdes: Max Multidevs
     *     upstream: Upstream
     *     php_version: PHP Version
     *     holder_type: Holder Type
     *     holder_id: Holder ID
     *     owner: Owner
     *     frozen: Is Frozen?
     *     last_frozen_at: Date Last Frozen
     * @return PropertyList
     *
     * @param string $site The name or UUID of a site to retrieve information on
     *
     * @usage <site> Displays <site>'s information.
     */
    public function info($site)
    {
        return new PropertyList($this->sites->get($site)->serialize());
    }
}
