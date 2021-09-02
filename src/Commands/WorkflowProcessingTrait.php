<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Class WorkflowTrait
 * @package Pantheon\Terminus\Commands
 */
trait WorkflowProcessingTrait
{
    /**
     * @param Workflow $model A workflow to run
     * @return Workflow That same workflow
     */
    public function processWorkflow(Workflow $workflow): ?Workflow
    {
        if ($this->input()->isInteractive()) {
            $progressBar = new WorkflowProgressBar($this->output(), $workflow);
            $this->getContainer()->inflect($progressBar);
            return $progressBar->cycle();
        }
        $retry_interval = $this->getConfig()->get('http_retry_delay_ms', 100);
        do {
            $workflow->fetch();
            usleep($retry_interval * 1000);
        } while (!$workflow->isFinished());
        return $workflow;
    }
}
