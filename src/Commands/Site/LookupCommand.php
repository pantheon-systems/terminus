<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class LookupCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class LookupCommand extends SiteCommand
{
    /**
     * Looks up a site name
     *
     * @authorized
     *
     * @command site:lookup
     *
     * @field-labels
     *   id: ID
     *   name: Name
     * @default-string-field id
     * @param string $site_name Name of a site to look up
     * @usage terminus site:lookup <site_name>
     *   * Responds with the UUID of a site if it exists and you have access to it
     *   * Responds that you are forbidden if you access a site that exists
     *      but you do not have access to it
     *   * Responds that a site does not exist
     * @return PropertyList
     */
    public function lookup($site_name)
    {
        $response = (array)$this->sites()->findUuidByName($site_name);
        return new PropertyList($response);
    }
}
