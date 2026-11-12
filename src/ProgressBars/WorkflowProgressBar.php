<?php

namespace Pantheon\Terminus\ProgressBars;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Utility\ProgramStatus;
use Pantheon\Terminus\Models\Workflow;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Pantheon\Terminus\ProgressBars\TerminusProgressBar;

/**
 * Class WorkflowProgressBar
 *
 * A progress bar that tracks the progress of a workflow.
 * TODO: Integrate this code back into Terminus core WorkflowProgressBar.
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
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ProgramStatus
     */
    protected $program_status;

    /**
     * WorkflowProgressBar constructor.
     * @param OutputInterface $output
     * @param Workflow $workflow
     */
    public function __construct(OutputInterface $output, Workflow $workflow)
    {
        $this->workflow = $workflow;
        $this->output = $output;
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
    public function cycle(int $timeout = 0)
    {
        $current_time = time();
        if ($timeout > 0) {
            $end_time = $current_time + $timeout;
        } else {
            $end_time = 0;
        }
        $this->reportWorking();
        while ($this->update()) {
            if ($end_time > 0 && $current_time >= $end_time) {
                $this->programStatus()->error("Workflow timed out after $timeout seconds.");
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $timeout]
                );
            }
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
            $this->programStatus()->error($e->getMessage());
            throw $e;
        }
        $this->updateActiveMessage();
        if ($this->workflow->isFinished()) {
            $this->end();
            // If the workflow failed then figure out the correct output message and throw an exception.
            if (!$this->workflow->isSuccessful()) {
                $this->programStatus()->error($this->workflow->getMessage());
                throw new TerminusException($this->workflow->getMessage());
            }
            $this->programStatus()->done($this->getDescription() . ' finished.');
            return false;
        }
        $step_after_fetch = $this->workflow->get('step');

        $this->progress_bar->advance($step_after_fetch - $step_before_fetch);
        $this->reportWorking();
        return true;
    }

    /**
     * Gets the program status reporter, which needs config and so can't be
     * built in the constructor.
     *
     * @return ProgramStatus
     */
    protected function programStatus()
    {
        if ($this->program_status === null) {
            $this->program_status = new ProgramStatus(
                $this->output,
                ProgramStatus::isEnabled($this->getConfig())
            );
        }
        return $this->program_status;
    }

    /**
     * Reports the workflow's current step to the terminal.
     */
    protected function reportWorking()
    {
        $total = (int) ($this->workflow->get('number_of_tasks') ?? 0);
        $step = (int) ($this->workflow->get('step') ?? 0);
        $progress = $total > 0 ? intdiv(100 * $step, $total) : null;
        $active = $this->workflow->get('active_description');
        $message = empty($active) ? $this->getDescription() : $this->getDescription() . ': ' . $active;
        $this->programStatus()->working($message, $progress);
    }

    /**
     * Gets the workflow's description, such as "Deploy code to live".
     *
     * @return string
     */
    protected function getDescription()
    {
        $description = str_replace('"', '', (string) $this->workflow->get('description'));
        return $description === '' ? 'Workflow' : $description;
    }

    /**
     * Updates the active message on the progress bar.
     */
    protected function updateActiveMessage()
    {
        $this->progress_bar->setMessage($this->workflow->get('active_description') ?? "Working...");
    }
}
