<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Class WorkflowTrait
 * @package Pantheon\Terminus\Commands
 */
trait WorkflowProcessingTrait
{
    /**
     * @param Workflow $workflow A workflow to run
     * @param int $timeout Optional timeout in seconds (0 = no timeout)
     * @return Workflow That same workflow
     */
    public function processWorkflow(Workflow $workflow, int $timeout = 0): ?Workflow
    {
        if ($this->input()->isInteractive()) {
            $nickname = \uniqid(__METHOD__ . "-");
            $this->getContainer()->add($nickname, WorkflowProgressBar::class)
                ->addArguments([$this->output(), $workflow]);
            $progressBar = $this->getContainer()->get($nickname);
            return $progressBar->cycle($timeout);
        }
        $retry_interval = $this->getConfig()->get('workflow_polling_delay_ms', 5000);
        if ($retry_interval < 1000) {
            // The API will not allow polling faster than once per second.
            $retry_interval = 1000;
        }
        $current_time = time();
        if ($timeout > 0) {
            $end_time = $current_time + $timeout;
        } else {
            $end_time = 0;
        }
        do {
            $current_time = time();
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $timeout]
                );
            }
            $workflow->fetch();
            usleep($retry_interval * 1000);
        } while (!$workflow->isFinished());
        if (!$workflow->isSuccessful()) {
            throw new TerminusException($workflow->getMessage());
        }
        return $workflow;
    }

    /**
     * Wait for a workflow to finish.
     *
     * @param int $start_time
     *   The time the workflow started.
     * @param \Pantheon\Terminus\Models\Site $site
     *   The site object.
     * @param string $env_name
     *   The environment name.
     * @param string $expected_workflow_description
     *   The expected workflow description.
     * @param int $max_wait_in_seconds
     *   The maximum wait time in seconds.
     * @param int $max_not_found_attempts
     *   The maximum number of attempts to find the workflow.
     */
    protected function waitForWorkflow(
        int $start_time,
        Site $site,
        string $env_name,
        string $expected_workflow_description = '',
        int $max_wait_in_seconds = 180,
        int $max_not_found_attempts = 0
    ) {
        $workflow = null;
        if (empty($expected_workflow_description)) {
            $expected_workflow_description = "Sync code on $env_name";
        }

        $current_time = time();
        if ($max_wait_in_seconds > 0) {
            $end_time = $current_time + $max_wait_in_seconds;
        } else {
            $end_time = 0;
        }
        $not_found_attempts = 0;
        $workflows = $site->getWorkflows();

        do {
            $current_time = time();
            if ($max_not_found_attempts && $not_found_attempts === $max_not_found_attempts) {
                throw new TerminusException(
                    "Attempted '{max}' times, giving up waiting for workflow to be found",
                    ['max' => $max_not_found_attempts]
                );
            }

            // Check if the timeout has been reached and throw an exception if so.
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $max_wait_in_seconds]
                );
            }
            $site = $this->getSiteById($site->id);
            $workflows->reset();
            $workflows->setData();
            $workflow_items = $workflows->fetch(['paged' => false,])->all();
            foreach ($workflow_items as $current_workflow) {
                $workflow_created = $current_workflow->get('created_at');
                if ($workflow_created < $start_time) {
                    // We already passed the start time.
                    break;
                }
                $workflow_description = str_replace('"', '', $current_workflow->get('description'));
                if (($expected_workflow_description === $workflow_description)) {
                    $current_workflow->fetch();
                    $this->log()->notice(
                        "Workflow '{current}' {status}.",
                        ['current' => $workflow_description, 'status' => $current_workflow->getStatus()]
                    );
                    $workflow = $current_workflow;
                    break;
                }
            }
            if ($workflow) {
                $this->log()->debug("Workflow found: {workflow}", ['workflow' => $workflow_description]);
                break;
            }

            $not_found_attempts++;
        } while (empty($workflow));

        // If we get here, we have a workflow that is not finished.
        // We need to wait for it to finish.
        // At this point, we may have already spent some time waiting for the workflow to be found,
        // let's be forgiving and wait for the whole time again.
        $this->processWorkflow($workflow, $max_wait_in_seconds);
    }
}
