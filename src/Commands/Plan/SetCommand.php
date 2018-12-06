<?php

namespace Pantheon\Terminus\Commands\Plan;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Plan
 */
class SetCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

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
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
