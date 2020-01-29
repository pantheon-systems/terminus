<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays New Relic configuration.
     *
     * @authorize
     *
     * @command new-relic:info
     *
     * @field-labels
     *     name: Name
     *     status: Status
     *     subscribed: Subscribed On
     *     state: State
     * @return PropertyList
     *
     * @param string $site_id Site name
     *
     * @usage <site> Displays New Relic configuration for <site>.
     */
    public function info($site_id)
    {
        return $this->getPropertyList($this->getSite($site_id)->getNewRelic());
    }
}
