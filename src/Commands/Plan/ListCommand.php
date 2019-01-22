<?php

namespace Pantheon\Terminus\Commands\Plan;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Site\SiteCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Plan
 */
class ListCommand extends SiteCommand
{
    use StructuredListTrait;

    /**
     * Displays the list of available site plans.
     *
     * @authorize
     *
     * @command plan:list
     * @aliases plans
     *
     * @field-labels
     *     sku: SKU
     *     name: Name
     *     billing_cycle: Billing Cycle
     *     price: Price
     *     monthly_price: Monthly Price
     * @return RowsOfFields
     *
     * @param string $site_id The name or UUID of a site to view the available plans for
     *
     * @usage <site_id> Displays a list of plans available to <site>.
     */
    public function listPlans($site_id)
    {
        return $this->getRowsOfFields($this->getSite($site_id)->getPlans());
    }
}
