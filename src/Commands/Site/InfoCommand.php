<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class InfoCommand extends SiteCommand
{
    /**
     * Gets full site information
     *
     * @command site:info
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
     * @param string $site Name|UUID of a site to look up
     *
     * @usage terminus site:info <site>
     *   * Responds with the table view of site information
     *   * Responds that you are forbidden if you access a site that exists
     *      but you do not have access to it
     *   * Responds that a site does not exist
     * @usage terminus site:info --field=<field>
     *   * Responds with the single field of site information requested
     *   * Responds that you are forbidden if you access a site that exists
     *      but you do not have access to it
     *   * Responds that a site does not exist
     * @return AssociativeList
     */
    public function info($site)
    {
        return new AssociativeList($this->sites()->get($site)->serialize());
    }
}
