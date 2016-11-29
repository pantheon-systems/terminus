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
     * Look up a site by its name
     *
     * @authorize
     *
     * @command site:lookup
     *
     * @field-labels
     *   id: ID
     *   name: Name
     * @default-string-field id
     * @return PropertyList
     *
     * @param string $site_name Name of a site to look up
     *
     * @usage terminus site:lookup <site>
     *   * Responds with the UUID of <site> if it exists and you have access to it
     *   * Responds that you are forbidden if you access <site>, which exists, but you do not have access to it
     *   * Responds that <site> does not exist
     */
    public function lookup($site_name)
    {
        return new PropertyList((array)$this->sites()->findUuidByName($site_name));
    }
}
