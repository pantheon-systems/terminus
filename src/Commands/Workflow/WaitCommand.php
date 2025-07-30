<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class AwaitCommand.
 *
 * @package Pantheon\Terminus\Commands\Workflow
 */
class WaitCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

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
        } else {
            $this->log()->notice('Waiting for workflow on site {site} environment {env}.', [
                'site' => $site->getName(),
                'env' => $site_env_id,
            ]);
        }

        if (!$env) {
            throw new TerminusException(
                'Environment {env} does not exist for site {site}.',
                ['env' => $site_env_id, 'site' => $site->getName()]
            );
        } else {
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
    protected function waitForCommit(
        $startTime,
        $site,
        $env_name,
        $target_commit,
        $maxWaitInSeconds = 180
    ) {
        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }

        // Validate commit SHA format (allow shortened hashes of 7+ characters)
        if (!preg_match('/^[0-9a-f]{7,40}$/', $target_commit)) {
            throw new TerminusException(
                'Commit {commit} is not a valid commit SHA (must be 7-40 hexadecimal characters).',
                ['commit' => $target_commit]
            );
        }

        $this->log()->notice('Waiting for workflow with commit {commit} on environment {env}.', [
            'commit' => $target_commit,
            'env' => $env_name
        ]);


        $workflow = null;
        $retry_count = 0;
        $max_retries = 10;

        do {
            $current_time = time();

            // Check timeout
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow with commit {commit} timed out after {timeout} seconds.',
                    ['commit' => $target_commit, 'timeout' => $maxWaitInSeconds]
                );
            }

            // Fetch workflow logs using the logs/workflows endpoint
            $response = $this->request()->request("sites/{$site->id}/logs/workflows");
            $workflow_logs = $response['data'] ?? [];

            $this->log()->debug('Found {count} total workflow logs', ['count' => count($workflow_logs)]);

            // Filter for the target environment and commit
            $matching_workflows = [];

            foreach ($workflow_logs as $log) {
                // Check if this workflow is for the target environment
                if (isset($log->workflow->environment) && $log->workflow->environment === $env_name) {
                    // Check if this workflow has the target commit (support shortened hashes)
                    if (
                        isset($log->workflow->target_commit) &&
                        strpos($log->workflow->target_commit, $target_commit) === 0
                    ) {
                        // Check if workflow started after our start time
                        if (isset($log->workflow->started_at) && $log->workflow->started_at >= $startTime) {
                            $matching_workflows[] = $log;
                        }
                    }
                }
            }

            $this->log()->debug('Found {count} matching workflows for commit {commit} on env {env}', [
                'count' => count($matching_workflows),
                'commit' => $target_commit,
                'env' => $env_name
            ]);

            // Find the most recent matching workflow
            if (!empty($matching_workflows)) {
                // Sort by started_at descending to get the most recent
                usort($matching_workflows, function ($a, $b) {
                    return $b->workflow->started_at <=> $a->workflow->started_at;
                });

                $workflow = $matching_workflows[0];
                $this->log()->notice('Found workflow {id} with description "{description}" for commit {commit}', [
                    'id' => $workflow->workflow->id,
                    'description' => $workflow->workflow->description ?? 'N/A',
                    'commit' => $target_commit
                ]);
                break;
            }

            $retry_count++;
            if ($retry_count >= $max_retries) {
                throw new TerminusException(
                    'Workflow with commit {commit} not found after {retries} attempts.',
                    ['commit' => $target_commit, 'retries' => $max_retries]
                );
            }

            $this->log()->debug('Workflow not found, retrying... ({retry}/{max})', [
                'retry' => $retry_count,
                'max' => $max_retries
            ]);
            sleep(5);
        } while (!$workflow);

        // Now wait for the workflow to complete
        $this->log()->notice('Waiting for workflow {id} to complete...', ['id' => $workflow->workflow->id]);

        $retry_interval = $this->getConfig()->get('workflow_polling_delay_ms', 5000);
        if ($retry_interval < 1000) {
            $retry_interval = 1000;
        }

        do {
            $current_time = time();
            if ($end_time > 0 && $current_time >= $end_time) {
                throw new TerminusException(
                    'Workflow timed out after {timeout} seconds.',
                    ['timeout' => $maxWaitInSeconds]
                );
            }

            // Re-fetch workflow logs to get updated status
            $response = $this->request()->request("sites/{$site->id}/logs/workflows");
            $workflow_logs = $response['data'] ?? [];

            // Find our specific workflow
            $updated_workflow = null;
            foreach ($workflow_logs as $log) {
                if ($log->workflow->id === $workflow->workflow->id) {
                    $updated_workflow = $log;
                    break;
                }
            }

            if (!$updated_workflow) {
                throw new TerminusException(
                    'Workflow {id} disappeared during execution.',
                    ['id' => $workflow->workflow->id]
                );
            }

            $workflow = $updated_workflow;

            $this->log()->debug('Workflow {id} status: {status}', [
                'id' => $workflow->workflow->id,
                'status' => $workflow->workflow->status ?? 'unknown'
            ]);

            // Check if workflow is finished
            if (
                isset($workflow->workflow->status) &&
                in_array($workflow->workflow->status, ['Success', 'Failed', 'Aborted'])
            ) {
                break;
            }

            usleep($retry_interval * 1000);
        } while (true);

        // Check if workflow succeeded
        if ($workflow->workflow->status !== 'Success') {
            throw new TerminusException(
                'Workflow {id} failed with status: {status}',
                ['id' => $workflow->workflow->id, 'status' => $workflow->workflow->status]
            );
        }

        $this->log()->notice('Workflow {id} completed successfully for commit {commit}', [
            'id' => $workflow->workflow->id,
            'commit' => $target_commit
        ]);
    }
}
