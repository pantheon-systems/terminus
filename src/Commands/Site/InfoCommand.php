<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class InfoCommand extends SiteCommand
{
    use StructuredListTrait;

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
     *     region: Region
     *     organization: Organization
     *     plan_name: Plan
     *     max_num_cdes: Max Multidevs
     *     upstream: Upstream
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
        return $this->getPropertyList($this->sites->get($site));
    }
}
