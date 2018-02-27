<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class WatchCommand
 * @package Pantheon\Terminus\Commands\Workflow
 */
class WatchCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    const WORKFLOWS_WATCH_INTERVAL = 5;
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
     *
     * @command workflow:watch
     *
     * @param string $site_id Site name
     * @option integer $checks Times to query
     *
     * @usage <site> Streams new and finished workflows from <site> to the console.
     */
    public function watch($site_id, $options = ['checks' => null,])
    {
        $site = $this->getSite($site_id);
        if (!is_null($number_of_checks = $options['checks'])) {
            $number_of_checks = (integer)$number_of_checks;
        }

        $this->log()->notice('Watching workflows...');
        $site->getWorkflows()->fetchWithOperations();
        while (true) {
            $last_wf_created_at = $site->getWorkflows()->lastCreatedAt();
            $last_wf_finished_at = $site->getWorkflows()->lastFinishedAt();
            sleep(self::WORKFLOWS_WATCH_INTERVAL);
            // Clear cached data
            $site->getWorkflows()->setData([]);
            $site->getWorkflows()->fetchWithOperations();

            $workflows = $site->getWorkflows()->all();
            foreach ($workflows as $workflow) {
                if ($workflow->wasCreatedAfter($last_wf_created_at) && !$this->startedNoticeAlreadyEmitted($workflow)) {
                    $this->emitStartedNotice($workflow);
                }

                if ($workflow->wasFinishedAfter($last_wf_finished_at) && !$this->finishedNoticeAlreadyEmitted($workflow)) {
                    $this->emitFinishedNotice($workflow);
                    if ($workflow->get('has_operation_log_output')) {
                        $this->emitOperationLogs($workflow);
                    }
                }
            }
            if (!is_null($number_of_checks) && (--$number_of_checks < 1)) {
                break;
            }
        }
    }

    /**
     * Emits a workflow-finished notice
     *
     * @param Workflow $workflow
     */
    protected function emitFinishedNotice($workflow)
    {
        $date_format = $this->getConfig()->get('date_format');
        $finished_message = 'Finished workflow {id} {description} ({env}) at {time}';
        $finished_context = [
            'id'          => $workflow->id,
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date($date_format, $workflow->get('finished_at')),
        ];
        $this->log()->notice($finished_message, $finished_context);
        array_push($this->finished, $workflow->id);
    }

    /**
     * Emits workflow operation logs for a workflow
     *
     * @param Workflow $workflow
     */
    protected function emitOperationLogs($workflow)
    {
        $workflow->fetchWithLogs();
        $operations = $workflow->operations();
        foreach ($operations as $operation) {
            if ($operation->has('log_output')) {
                $this->log()->notice($operation);
            }
        }
    }

    /**
     * Emits a workflow-started notice
     *
     * @param Workflow $workflow
     */
    protected function emitStartedNotice($workflow)
    {
        $date_format = $this->getConfig()->get('date_format');
        $started_message = 'Started {id} {description} ({env}) at {time}';
        $started_context = [
            'id'          => $workflow->id,
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date($date_format, $workflow->get('started_at')),
        ];
        $this->log()->notice($started_message, $started_context);
        array_push($this->started, $workflow->id);
    }

    /**
     * Queries the finished-workflow list for this workflow and returns true if it is present
     *
     * @param Workflow $workflow
     * @return boolean
     */
    protected function finishedNoticeAlreadyEmitted($workflow)
    {
        return in_array($workflow->id, $this->finished);
    }

    /**
     * Queries the started-workflow list for this workflow and returns true if it is present
     *
     * @param Workflow $workflow
     * @return boolean
     */
    protected function startedNoticeAlreadyEmitted($workflow)
    {
        return in_array($workflow->id, $this->started);
    }
}
