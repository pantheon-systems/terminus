<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class StatusCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays New Relic configuration.
     *
     * @authorize
     *
     * @command new-relic:status
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
     * @usage terminus new-relic:status <site>
     *     Displays New Relic configuration for <site>.
     */
    public function status($site_id)
    {
        return new PropertyList($this->getSite($site_id)->getNewRelic()->serialize());
    }
}
