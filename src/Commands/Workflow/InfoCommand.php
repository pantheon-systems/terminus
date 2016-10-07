<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\MultiCommand;

class InfoCommand extends MultiCommand
{
    /**
     * Show all details for a workflow including operations.
     *
     * @command workflow:info
     *
     * @param string $site_id Site name that the workflow is part of.
     *
     * @option string $workflow-id ID of the workflow to show.
     * @option string $latest-with-logs Just show the latest workflow with logs.
     *
     * @usage terminus workflow:info <site_name> <wf-id>
     *   Show the workflow with id <wf-id> found on site <site_name>.
     */
    public function info($site_id, $options = ['latest-with-logs' => false, 'workflow-id' => ''])
    {
        $this->subCommand('workflow:info:status', $this->getInput(), $this->getOutput());
        $this->subCommand('workflow:info:operations', $this->getInput(), $this->getOutput());
        $this->subCommand('workflow:info:logs', $this->getInput(), $this->getOutput());
    }
}
