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
class WaitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Wait for a workflow to complete. Usually this will be used to wait
     * for code commits, since Terminus will already wait for workflows
     * that it starts through the API.
     *
     * @command workflow:wait
     * @param $site_env_id The pantheon site to wait for.
     * @param $description The workflow description to wait for. Optional; default is code sync.
     * @option start Ignore any workflows started prior to the start time (epoch). Required if --commit is not provided.
     * @option commit Commit sha to wait for. Required if --start is not provided.
     * @option max Maximum number of seconds to wait for the workflow to complete
     */
    public function workflowWait(
        $site_env_id,
        $description = '',
        $options = [
          'start' => null,
          'commit' => '',
          'max' => 180,
        ]
    ) {
        // Validate that at least one of start or commit is provided
        if (is_null($options['start']) && empty($options['commit'])) {
            throw new \Exception('You must specify either --start or --commit');
        }

        list($site, $env) = $this->getSiteEnv($site_env_id);
        $env_name = $env->getName();

        $startTime = $options['start'];
        if (is_null($startTime)) {
            $startTime = time() - 60;
        }
        
        if (!empty($options['commit'])) {
            $this->waitForCommit($startTime, $site, $env_name, $options['commit'], $options['max']);
            return;
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
            $site = $this->getSiteById($site->id);
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

    /**
     * Wait for a workflow with a given commit to complete.
     */
    public function waitForCommit(
        $startTime,
        $site,
        $env_name,
        $target_commit,
        $maxWaitInSeconds = 180,
        $maxNotFoundAttempts = null
    ) {
        $wfl = null;
        $wflc = $site->getWorkflowLogs();
        if (!$wflc instanceof WorkflowLogsCollection) {
            throw new TerminusException('Workflow logs could not be retrieved for site: {site}', ['site' => $site_id,]);
        }

        // Remove workflows that are not for the environment $env_name.
        $wflc->filterForEnvironment($env_name);

        // Find the latest workflow that matches the commit hash
        $wfl = $wflc->findLatestFromOptionsArray([
            'target_commit' => $target_commit,
        ]);

        // If we didn't find a workflow, then we need to wait for one to be created
        if (!$wfl instanceof WorkflowLog) {
            // sleep to give the workflow time to be created
            sleep($this->getConfig()->get('refresh_workflow_delay', 30));
            $wfl = $wflc->fetch()->findLatestFromOptionsArray([
                'target_commit' => $target_commit,
            ]);
            if ($startTime->diff(new \DateTime())->s > $maxWaitInSeconds) {
                throw new TerminusException('Exceeded maximum wait time of {max} seconds.', ['max' => $maxWaitInSeconds]);
            }
        }

        while (!$wfl->isFinished()) {
            if ($startTime->diff(new \DateTime())->s > $maxWaitInSeconds) {
                throw new TerminusException('Exceeded maximum wait time of {max} seconds.', ['max' => $maxWaitInSeconds]);
            }
            $this->log()->notice('Waiting for workflow {id} to complete.', ['id' => $wfl->id,]);
            sleep($this->getConfig()->get('refresh_workflow_delay', 30));
            $wfl->fetch();
        }
        $this->log()->notice('Workflow {id} has completed with status {status}.', [
            'id' => $wfl->id,
            'status' => $wfl->get('status'),
        ]);
    }
}
