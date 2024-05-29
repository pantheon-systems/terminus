<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Collections\WorkflowLogsCollection;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Models\WorkflowLog;
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

    public const WORKFLOWS_WATCH_INTERVAL = 5;
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
     * Streams workflows from a site to the console until their status = complete.
     *
     * @authorize
     *
     * @command workflow:watch
     *
     * @option integer $checks Times to query
     *
     * @usage <site> Streams workflows from <site> / <site>.<env> to the console.
     *
     * @param string $site_env {Sitename}.{Env}. The .env is optional
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function watch($site_env, $options = ['checks' => null]): int
    {
        // 1. Get the siteID from the site name
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);
        // Get all of the workflow Logs for the site
        $wflc = $site->getWorkflowLogs();
        // if the environment is not empty, filter by environment
        if (!$wflc instanceof WorkflowLogsCollection) {
            $this->log()->error('Unable to get workflow for site.');
            return -1;
        }
        if (!empty($env)) {
            $wflc->filterForEnvironment($env);
        }
        // Get the latest workflow log
        $wfl = $wflc->latest();
        // If there are no workflows, return
        if (!$wfl instanceof WorkflowLog) {
            $this->log()->error('No workflows found.');
            return false;
        }
        // If the workflow is already finished, return
        while (!$wfl->isFinished()) {
            $this->emitWorkflowLogs($wfl);
            sleep(self::WORKFLOWS_WATCH_INTERVAL);
            $wfl->fetch();
        }
        return $wfl->isSuccessful();
    }

    /**
     * Emits a workflow-finished notice.
     *
     * @param \Pantheon\Terminus\Models\WorkflowLog $workflow
     */
    protected function emitFinishedNotice(WorkflowLog $wfl)
    {
        $date_format = $this->getConfig()->get('date_format');
        $finished_message = 'Finished workflow {id} {description} ({env}) at {time}';
        $finished_context = [
            'id' => $wfl->id,
            'description' => $wfl->get('description'),
            'env' => $wfl->get('environment'),
            'time' => date($date_format, $wfl->get('finished_at')),
        ];
        $this->log()->notice($finished_message, $finished_context);
        array_push($this->finished, $wfl->id);
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
            'id' => $workflow->id,
            'description' => $workflow->get('description'),
            'env' => $workflow->get('environment'),
            'time' => date($date_format, $workflow->getStartedAt()),
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
}
