<?php

namespace Pantheon\Terminus\ProgressBars;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WorkflowProgressBar
 *
 * A progress bar that tracks the progress of a workflow.
 *
 * @package Pantheon\Terminus\ProgressBars
 */
class WorkflowProgressBar extends TerminusProgressBar
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * WorkflowProgressBar constructor.
     * @param OutputInterface $output
     * @param Workflow $workflow
     */
    public function __construct(OutputInterface $output, Workflow $workflow)
    {
        $this->workflow = $workflow;
        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %message%');
        $this->progress_bar = new ProgressBar($output, $this->workflow->get('number_of_tasks') ?? 3);
        $this->updateActiveMessage();
        $this->progress_bar->setFormat('custom');
        $this->start();
    }

    /**
     * Runs the progress bar until completion.
     * @throws TerminusException
     */
    public function cycle()
    {
        while ($this->update()) {
            $this->sleep();
        }
    }

    /**
     * Sleeps to prevent spamming the API.
     */
    protected function sleep()
    {
        $retry_interval = $this->getConfig()->get('workflow_polling_delay_ms', 5000);
        if ($retry_interval < 1000) {
            // The API will not allow polling faster than once per second.
            $retry_interval = 1000;
        }
        usleep($retry_interval * 1000);
    }

    /**
     * Runs a single iteration of the progress bar.
     * @return bool
     * @throws TerminusException
     */
    protected function update()
    {
        $step_before_fetch = $this->workflow->get('step');
        try {
            $this->workflow->fetch();
        } catch (\Exception $e) {
            $this->end();
            throw $e;
        }
        $this->updateActiveMessage();
        if ($this->workflow->isFinished()) {
            $this->end();
            // If the workflow failed then figure out the correct output message and throw an exception.
            if (!$this->workflow->isSuccessful()) {
                throw new TerminusException($this->workflow->getMessage());
            }
            return false;
        }
        $step_after_fetch = $this->workflow->get('step');

        $this->progress_bar->advance($step_after_fetch - $step_before_fetch);
        return true;
    }

    /**
     * Updates the active message on the progress bar.
     */
    protected function updateActiveMessage()
    {
        $this->progress_bar->setMessage($this->workflow->get('active_description') ?? "Working...");
    }
}
