<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\Workflow\Info\InfoBaseCommand;

class StatusCommand extends InfoBaseCommand
{
    /**
     * Show status information about a specific workflow.
     *
     * @command workflow:info:status
     *
     * @param string $site_id Site name that the workflow is part of.
     *
     * @option string $workflow-id ID of the workflow to show.
     * @option string $latest-with-logs Just show the latest workflow with logs.
     *
     * @return AssociativeList
     *
     * @field-labels
     *   id: Workflow ID
     *   env: Environment
     *   workflow: Workflow
     *   user: User
     *   status: Status
     *   time: Time
     *
     * @usage terminus workflow:info:status <site_name> <wf-id>
     *   Show the workflow with id <wf-id> found on site <site_name>.
     */
    public function status($site_id, $options = ['latest-with-logs' => false, 'workflow-id' => ''])
    {
        $workflow = $this->getWorkflow($site_id, $options);
        if (!$workflow) {
            return;
        }
        $workflow_data = $workflow->serialize();
        unset($workflow_data['operations']);
        return new AssociativeList($workflow_data);
    }
}
