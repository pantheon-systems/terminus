<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Collections\WorkflowLogsCollection;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\WorkflowLog;

class WaitForCommitCommand extends TerminusCommand
{
    /**
     * Wait for a workflow to complete. Usually this will be used to wait
     * for code commits, since Terminus will already wait for workflows
     * that it starts through the API.
     *
     * @command workflow:wait:commit
     * @param string $site_id The pantheon site to wait for.
     * @option string commit_hash commit id to wait for
     * @option max Maximum number of seconds to wait for the workflow to complete
     */
    public function waitForCommit(
        string $site_id,
        array $options = [
          'commit_hash' => null,
          'max' => 180,
        ]
    ) {
        $wfl = null;
        $site = $this->getSiteById($site_id);
        $wflc = $site->getWorkflowLogs();
        if (!$wflc instanceof WorkflowLogsCollection) {
            throw new TerminusException('Workflow logs could not be retrieved.');
        }
        if ($options['commit_hash'] !== null) {
            $wfl = $wflc->findLatestByProperty('commit_hash', $options['commit_hash']);
        }
        if (!$wfl instanceof WorkflowLog) {
            $wfl = $wflc->latest();
        }

        while (!$wfl->isFinished()) {
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
