<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

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
     * @authorize
     * @interact
     *
     * @command workflow:wait
     * @param $site_env_id The pantheon site to wait for.
     * @param $description The workflow description to wait for. Optional; default is code sync.
     * @option start Ignore any workflows started prior to the start time (epoch)
     * @option commit Commit sha to wait for
     * @option max Maximum number of seconds to wait for the workflow to complete
     */
    public function workflowWait(
        $site_env_id,
        $description = '',
        $options = [
          'start' => 0,
          'commit' => '',
          'max' => 180,
        ]
    ) {
        list($site, $env) = $this->getSiteEnv($site_env_id);
        if (!$site instanceof \Pantheon\Terminus\Models\Site) {
            throw new TerminusException(
                'Site {site} does not exist.',
                ['site' => $site_env_id]
            );
        }
        else {
            $this->log()->notice('Waiting for workflow on site {site} environment {env}.', [
                'site' => $site->getName(),
                'env' => $site_env_id,
            ]);
        }
        // print_r($site);
        if (!$env) {
            throw new TerminusException(
                'Environment {env} does not exist for site {site}.',
                ['env' => $site_env_id, 'site' => $site->getName()]
            );
        }
        else {
            $this->log()->notice('Waiting for workflow on environment {env}.', ['env' => $env->getName()]);
        }
        $env_name = $env->getName();

        $startTime = $options['start'];
        if (!$startTime) {
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
        $workflow = null;
        if (empty($expectedWorkflowDescription)) {
            $expectedWorkflowDescription = "Sync code on $env_name";
        }

        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }
        $not_found_attempts = 0;
        $workflows = $site->getWorkflows();

        do {
            $current_time = time();
            if ($maxNotFoundAttempts && $not_found_attempts === $maxNotFoundAttempts) {
                throw new TerminusException(
                    "Attempted '{max}' times, giving up waiting for workflow to be found",
                    ['max' => $maxNotFoundAttempts]
                );
            }

            // Check if the timeout has been reached and throw an exception if so.
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $maxWaitInSeconds]
                );
            }
            $site = $this->getSiteById($site->id);
            $workflows->reset();
            $workflows->setData();
            $workflow_items = $workflows->fetch(['paged' => false,])->all();
            foreach ($workflow_items as $current_workflow) {
                $workflow_created = $current_workflow->get('created_at');
                if ($workflow_created < $startTime) {
                    // We already passed the start time.
                    break;
                }
                $workflow_description = str_replace('"', '', $current_workflow->get('description'));
                if (($expectedWorkflowDescription === $workflow_description)) {
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
            sleep(5);
        } while (empty($workflow));

        // If we get here, we have a workflow that is not finished.
        // We need to wait for it to finish.
        // At this point, we may have already spent some time waiting for the workflow to be found,
        // let's be forgiving and wait for the whole time again.
        $retry_interval = $this->getConfig()->get('workflow_polling_delay_ms', 5000);
        if ($retry_interval < 1000) {
            // The API will not allow polling faster than once per second.
            $retry_interval = 1000;
        }
        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }
        do {
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $maxWaitInSeconds]
                );
            }
            $workflow->fetch();
            usleep($retry_interval * 1000);
            $current_time = time();
        } while (!$workflow->isFinished());
        if (!$workflow->isSuccessful()) {
            throw new TerminusException($workflow->getMessage());
        }
        $this->log()->notice("Workflow succeeded");
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
            throw new TerminusException('Workflow logs could not be retrieved for site: {site}', ['site' => $site->id,]);
        }

        // Remove workflows that are not for the environment $env_name.
        $wflc->filterForEnvironment($env_name);

        // Find the latest workflow that matches the commit hash
        $wfl = $wflc->findLatestFromOptionsArray([
            'target_commit' => $target_commit,
        ]);

        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }

        // If we didn't find a workflow, then we need to wait for one to be created
        if (!$wfl instanceof WorkflowLog) {
            // sleep to give the workflow time to be created
            sleep($this->getConfig()->get('refresh_workflow_delay', 30));
            $wfl = $wflc->fetch()->findLatestFromOptionsArray([
                'target_commit' => $target_commit,
            ]);
            $current_time = time();
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException('Exceeded maximum wait time of {max} seconds.', ['max' => $maxWaitInSeconds]);
            }
        }

        while (!$wfl->isFinished()) {
            $current_time = time();
            if ($end_time > 0 && $current_time >= $end_time) {
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
