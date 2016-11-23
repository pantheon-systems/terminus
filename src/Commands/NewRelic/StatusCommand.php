<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class StatusCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieves the New Relic status of a site
     *
     * @authorized
     *
     * @command new-relic:status
     *
     * @param string $site_id Name of the site to check New Relic status on
     *
     * @field-labels
     *   name: Name
     *   status: Status
     *   subscribed: Subscribed On
     *   state: State
     *
     * @usage terminus new-relic:status my-site
     *   Checks the New Relic status for the site named 'my-site'.
     * @return PropertyList
     */
    public function status($site_id)
    {
        return new PropertyList($this->getSite($site_id)->getNewRelic()->serialize());
    }
}
