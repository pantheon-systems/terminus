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
     * Get information about a site
     *
     * @authorize
     *
     * @command site:info
     * @aliases site
     *
     * @field-labels
     *   id: ID
     *   name: Name
     *   label: Label
     *   created: Created
     *   framework: Framework
     *   organization: Organization
     *   service_level: Service Level
     *   upstream: Upstream
     *   php_version: PHP Version
     *   holder_type: Holder Type
     *   holder_id: Holder ID
     *   owner: Owner
     * @return PropertyList
     *
     * @param string $site The name or UUID of a site to retrieve information on
     *
     * @usage terminus site:info <site>
     *   Responds with the table view of information about <site>
     */
    public function info($site)
    {
        return new PropertyList($this->sites->get($site)->serialize());
    }
}
