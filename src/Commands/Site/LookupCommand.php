<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Collections\Sites;

class LookupCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Looks up a site name
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
     * @return AssociativeList
     */
    public function lookup($site_name)
    {
        $response = (array)$this->sites()->findUuidByName($site_name);
        return new AssociativeList($response);
    }
}
