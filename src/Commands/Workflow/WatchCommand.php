<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class WatchCommand.
 *
 * @package Pantheon\Terminus\Commands\Workflow
 */
class WatchCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    public const WORKFLOWS_WATCH_INTERVAL_FAST  = 10; // poll interval for the first ~5 minutes
    public const WORKFLOWS_WATCH_INTERVAL_SLOW  = 30; // poll interval after warmup
    public const WORKFLOWS_WATCH_WARMUP         = 30; // number of fast polls before slowing down (~5 min)
    public const WORKFLOWS_WATCH_DEFAULT_TIMEOUT = 15; // default timeout in minutes (0 = unlimited)
    /**
     * @var array We keep track of workflows that have been printed. This is necessary because the local clock may
     * drift from the server's clock, causing events to be printed twice.
     */
    private $finished = [];
    /**
     * @var array
     */
    private $started = [];

    /**
     * Streams new and finished workflows from a site to the console.
     *
     * @authorize
     * @interact
     *
     * @command workflow:watch
     *
     * @option integer $timeout Minutes before giving up (default: 15). Pass 0 for no limit.
     *
     * @usage <site> Streams new and finished workflows from <site> to the console.
     *
     * @param string $site_id Site name
     * @param array $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function watch($site_id, $options = ['timeout' => self::WORKFLOWS_WATCH_DEFAULT_TIMEOUT])
    {
        $site = $this->getSiteById($site_id);
        $timeout  = (int)$options['timeout'] * 60; // convert minutes to seconds; 0 = unlimited
        $poll_count = 0;
        $elapsed    = 0;

        $this->log()->notice('Watching workflows...');
        $site->getWorkflows()->fetchWithOperations();
        while (true) {
            $poll_count++;
            $interval = ($poll_count <= self::WORKFLOWS_WATCH_WARMUP)
                ? self::WORKFLOWS_WATCH_INTERVAL_FAST
                : self::WORKFLOWS_WATCH_INTERVAL_SLOW;
            $this->sleep($interval);
            $elapsed += $interval;

            // Clear cached data
            $last_wf_created_at = $site->getWorkflows()->lastCreatedAt();
            $last_wf_finished_at = $site->getWorkflows()->lastFinishedAt();
            $site->getWorkflows()->setData([]);
            $site->getWorkflows()->fetchWithOperations();

            $workflows = $site->getWorkflows()->all();
            foreach ($workflows as $workflow) {
                /** @var \Pantheon\Terminus\Models\Workflow $workflow */
                if ($workflow->wasCreatedAfter($last_wf_created_at) && !$this->startedNoticeAlreadyEmitted($workflow)) {
                    $this->emitStartedNotice($workflow);
                }

                if (
                    $workflow->wasFinishedAfter($last_wf_finished_at)
                    && !$this->finishedNoticeAlreadyEmitted($workflow)
                ) {
                    $this->emitFinishedNotice($workflow);
                    if ($workflow->get('has_operation_log_output')) {
                        $this->emitOperationLogs($workflow);
                    }
                }
            }

            if ($timeout > 0 && $elapsed >= $timeout) {
                $this->log()->warning(
                    'Workflow watch timed out after {minutes} minutes. Use --timeout=0 for no limit.',
                    ['minutes' => $timeout / 60]
                );
                break;
            }
        }
    }

    /**
     * Emits a workflow-finished notice.
     *
     * @param \Pantheon\Terminus\Models\Workflow $workflow
     */
    protected function emitFinishedNotice($workflow)
    {
        $date_format = $this->getConfig()->get('date_format');
        $finished_message = 'Finished workflow {id} {description} ({env}) at {time}';
        $finished_context = [
            'id'          => $workflow->id,
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date($date_format, $workflow->getFinishedAt()),
        ];
        $this->log()->notice($finished_message, $finished_context);
        array_push($this->finished, $workflow->id);
    }

    /**
     * Emits workflow operation logs for a workflow.
     *
     * @param \Pantheon\Terminus\Models\Workflow $workflow
     */
    protected function emitOperationLogs($workflow)
    {
        $workflow->fetchWithLogs();
        $operations = $workflow->getOperations()->all();
        foreach ($operations as $operation) {
            if ($operation->has('log_output')) {
                $this->log()->notice($operation);
            }
        }
    }

    /**
     * Emits a workflow-started notice.
     *
     * @param \Pantheon\Terminus\Models\Workflow $workflow
     */
    protected function emitStartedNotice($workflow)
    {
        $date_format = $this->getConfig()->get('date_format');
        $started_message = 'Started {id} {description} ({env}) at {time}';
        $started_context = [
            'id'          => $workflow->id,
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date($date_format, $workflow->getStartedAt()),
        ];
        $this->log()->notice($started_message, $started_context);
        array_push($this->started, $workflow->id);
    }

    /**
     * Queries the finished-workflow list for this workflow and returns true if it is present.
     *
     * @param \Pantheon\Terminus\Models\Workflow $workflow
     *
     * @return boolean
     */
    protected function finishedNoticeAlreadyEmitted($workflow)
    {
        return in_array($workflow->id, $this->finished);
    }

    /**
     * Queries the started-workflow list for this workflow and returns true if it is present.
     *
     * @param \Pantheon\Terminus\Models\Workflow $workflow
     *
     * @return boolean
     */
    protected function startedNoticeAlreadyEmitted($workflow)
    {
        return in_array($workflow->id, $this->started);
    }

    /**
     * Pause execution for a number of seconds. Extracted for testability.
     *
     * @param int $seconds
     */
    protected function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
