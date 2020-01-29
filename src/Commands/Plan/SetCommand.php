<?php

namespace Pantheon\Terminus\Commands\Plan;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Plan
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Changes a site's plan.
     *
     * @authorize
     *
     * @command plan:set
     *
     * @param string $site_id Site name
     * @param string $plan_id The SKU or UUID of the plan to set
     *
     * @usage <site> <plan> Updates <site>'s plan to <plan>.
     */
    public function set($site_id, $plan_id)
    {
        $site = $this->getSite($site_id);
        $plans = $site->getPlans();
        $workflow = $plans->set($plans->get($plan_id));
        $this->log()->notice('Setting plan of "{site_id}" to "{plan_id}".', compact('site_id', 'plan_id'));
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
