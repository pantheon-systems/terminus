<?php

namespace Pantheon\Terminus\Commands\Plan;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Site\SiteCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Plan
 */
class InfoCommand extends SiteCommand
{
    use StructuredListTrait;

    /**
     * Displays information about a site's plan.
     *
     * @authorize
     *
     * @command plan:info
     *
     * @field-labels
     *     id: ID
     *     sku: SKU
     *     name: Name
     *     billing_cycle: Billing Cycle
     *     price: Price
     *     monthly_price: Monthly Price
     *     automated_backups: Automated Backups
     *     cache_server: Cache Server
     *     custom_upstreams: Custom Upstreams
     *     multidev: Multidev Environments
     *     new_relic: New Relic
     *     rackspace_ssl: Rackspace SSL
     *     secure_runtime_access: Secure Runtime Access
     *     storage: Storage (GB)
     *     support_plan: Support Plan
     * @return PropertyList
     *
     * @param string $site The name or UUID of a site to retrieve current plan information on
     *
     * @usage <site> Displays <site>'s current plan information.
     */
    public function info($site)
    {
        return $this->getPropertyList($this->sites->get($site)->getPlan()->fetch());
    }
}
