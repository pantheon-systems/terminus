<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AwaitCommand.
 *
 * @package Pantheon\Terminus\Commands\Workflow
 */
class AwaitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Wait for a workflow to complete. Usually this will be used to wait
     * for code commits, since Terminus will already wait for workflows
     * that it starts through the API.
     *
     * @command workflow:await
     * @param $site_env_id The pantheon site to wait for.
     * @param $description The workflow description to wait for. Optional; default is code sync.
     * @option start Ignore any workflows started prior to the start time (epoch)
     * @option max Maximum number of seconds to wait for the workflow to complete
     */
    public function workflowAwait(
        $site_env_id,
        $description = '',
        $options = [
          'start' => 0,
          'max' => 180,
        ]
    ) {
        list($site, $env) = $this->getSiteEnv($site_env_id);
        $env_name = $env->getName();

        $startTime = $options['start'];
        if (!$startTime) {
            $startTime = time() - 60;
        }
        $this->waitForWorkflow($startTime, $site, $env_name, $description, $options['max']);
    }

    protected function waitForWorkflow(
        $startTime,
        $site,
        $env_name,
        $expectedWorkflowDescription = '',
        $maxWaitInSeconds = 180,
        $maxNotFoundAttempts = null
    ) {
        if (empty($expectedWorkflowDescription)) {
            $expectedWorkflowDescription = "Sync code on $env_name";
        }

        $startWaiting = time();
        $firstWorkflowDescription = null;
        $notFoundAttempts = 0;
        $workflows = $site->getWorkflows();

        while (true) {
            $site = $this->getsite($site->id);
            // Refresh env on each interation.
            $index = 0;
            $workflows->reset();
            $workflow_items = $workflows->fetch(['paged' => false,])->all();
            $found = false;
            foreach ($workflow_items as $workflow) {
                $workflowCreationTime = $workflow->get('created_at');

                $workflowDescription = str_replace('"', '', $workflow->get('description'));
                if ($index === 0) {
                    $firstWorkflowDescription = $workflowDescription;
                }
                $index++;

                if ($workflowCreationTime < $startTime) {
                    // We already passed the start time.
                    break;
                }

                if (($expectedWorkflowDescription === $workflowDescription)) {
                    $workflow->fetch();
                    $this->log()->notice(
                        "Workflow '{current}' {status}.",
                        ['current' => $workflowDescription, 'status' => $workflow->getStatus()]
                    );
                    $found = true;
                    if ($workflow->isSuccessful()) {
                        $this->log()->notice("Workflow succeeded");
                        return;
                    }
                }
            }
            if (!$found) {
                $notFoundAttempts++;
                $this->log()->notice(
                    "Current workflow is '{current}'; waiting for '{expected}'",
                    ['current' => $firstWorkflowDescription, 'expected' => $expectedWorkflowDescription]
                );
                if ($maxNotFoundAttempts && $notFoundAttempts === $maxNotFoundAttempts) {
                    $this->log()->warning(
                        "Attempted '{max}' times, giving up waiting for workflow to be found",
                        ['max' => $maxNotFoundAttempts]
                    );
                    break;
                }
            }
            // Wait a bit, then spin some more
            sleep(5);
            if (time() - $startWaiting >= $maxWaitInSeconds) {
                $this->log()->warning(
                    "Waited '{max}' seconds, giving up waiting for workflow to finish",
                    ['max' => $maxWaitInSeconds]
                );
                break;
            }
        }
    }
}
