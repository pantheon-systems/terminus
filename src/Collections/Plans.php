<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Plan;

/**
 * Class Plans
 * @package Pantheon\Terminus\Collections
 */
class Plans extends SiteOwnedCollection
{
    const PRETTY_NAME = 'plans';
    /**
     * @var string
     */
    protected $collected_class = Plan::class;
    /**
     * @var string
     */
    protected $url = 'accounts/site-account-forwarding/{site_id}/plans';

    /**
     * Sets the site's plan to the plan indicated.
     *
     * @param Plan $plan Plan to be set.
     * @return Workflow
     */
    public function set(Plan $plan)
    {
        return $this->getSite()->getWorkflows()->create(
            'change_site_service_level',
            ['params' => ['sku' => $plan->getSku(),],]
        );
    }
}
