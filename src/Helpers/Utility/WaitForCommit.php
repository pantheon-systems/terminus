<?php

namespace Pantheon\Terminus\Helpers\Utility;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Request\Request;
use Psr\Log\LoggerInterface;

/**
 * Utility class for waiting on a workflow matching a specific commit SHA.
 */
class WaitForCommit
{
    /**
     * Wait for a workflow with a given commit to complete.
     *
     * @param int $startTime Ignore workflows started before this epoch timestamp.
     * @param object $site The site model (must have an `id` property).
     * @param string $env_name The environment name to match.
     * @param string $target_commit The commit SHA (7-40 hex characters) to wait for.
     * @param Request $request A configured Request instance for API calls.
     * @param LoggerInterface $logger A PSR-3 logger.
     * @param int $maxWaitInSeconds Maximum seconds to wait before giving up.
     * @param int $pollingDelayMs Milliseconds between polling attempts.
     *
     * @throws TerminusException
     */
    public static function waitForCommit(
        $startTime,
        $site,
        $env_name,
        $target_commit,
        Request $request,
        LoggerInterface $logger,
        $maxWaitInSeconds = 180,
        $pollingDelayMs = 5000
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

        $logger->notice('Waiting for workflow with commit {commit} on environment {env}.', [
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
                $logger->warning(
                    'Waited \'{timeout}\' seconds, giving up waiting for workflow with commit {commit} to finish',
                    ['commit' => $target_commit, 'timeout' => $maxWaitInSeconds]
                );
                return;
            }

            // Fetch workflow logs using the logs/workflows endpoint
            $response = $request->request("sites/{$site->id}/logs/workflows");
            $workflow_logs = $response['data'] ?? [];

            $logger->debug('Found {count} total workflow logs', ['count' => count($workflow_logs)]);

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

            $logger->debug('Found {count} matching workflows for commit {commit} on env {env}', [
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
                $logger->notice('Found workflow {id} with description "{description}" for commit {commit}', [
                    'id' => $workflow->workflow->id,
                    'description' => $workflow->workflow->description ?? 'N/A',
                    'commit' => $target_commit
                ]);
                break;
            }

            $retry_count++;
            if ($retry_count >= $max_retries) {
                $logger->warning(
                    'Workflow with commit {commit} not found after {retries} attempts.',
                    ['commit' => $target_commit, 'retries' => $max_retries]
                );
                return;
            }

            $logger->debug('Workflow not found, retrying... ({retry}/{max})', [
                'retry' => $retry_count,
                'max' => $max_retries
            ]);
            sleep(5);
        } while (!$workflow);

        // Now wait for the workflow to complete
        $logger->notice('Waiting for workflow {id} to complete...', ['id' => $workflow->workflow->id]);

        $retry_interval = $pollingDelayMs;
        if ($retry_interval < 1000) {
            // The API will not allow polling faster than once per second.
            $retry_interval = 1000;
        }

        do {
            $current_time = time();
            if ($end_time > 0 && $current_time >= $end_time) {
                $logger->warning(
                    'Waited \'{timeout}\' seconds, giving up waiting for workflow to finish',
                    ['timeout' => $maxWaitInSeconds]
                );
                return;
            }

            // Re-fetch workflow logs to get updated status
            $response = $request->request("sites/{$site->id}/logs/workflows");
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

            $logger->debug('Workflow {id} status: {status}', [
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

        $logger->notice('Workflow {id} completed successfully for commit {commit}', [
            'id' => $workflow->workflow->id,
            'commit' => $target_commit
        ]);
    }
}
