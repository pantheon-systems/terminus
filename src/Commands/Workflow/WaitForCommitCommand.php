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
     * @param string $target_commit commit id to wait for
     * @option max Maximum number of seconds to wait for the workflow to complete (default 600)
     */
    public function waitForCommit(
        string $site_id,
        string $target_commit,
        $options = ['max' => 600]
    ) {
        $wfl = null;
        $site = $this->getSiteById($site_id);
        $wflc = $site->getWorkflowLogs();
        if (!$wflc instanceof WorkflowLogsCollection) {
            throw new TerminusException('Workflow logs could not be retrieved.');
        }

        // Find the latest workflow that matches the commit hash
        $wfl = $wflc->findLatestFromOptionsArray([
            'target_commit' => $target_commit,
        ]);

        // If we didn't find a workflow, then we need to wait for one to be created
        if (!$wfl instanceof WorkflowLog) {
            $wfl = $wflc->latest();
        }

        $startTime = time();
        while (!$wfl->isFinished()) {
            $elapsed = time() - $startTime;
            if ($elapsed > $options['max']) {
                throw new TerminusException('Exceeded maximum wait time of {max} seconds.', ['max' => $options['max']]);
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
