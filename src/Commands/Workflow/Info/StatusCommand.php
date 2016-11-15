<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

class StatusCommand extends InfoBaseCommand
{
    /**
     * Show status information about a specific workflow.
     *
     * @command workflow:info:status
     *
     * @param string $site_id Name or ID of the site that the workflow is part of
     * @option string $id UUID of the workflow to show
     * @return PropertyList
     *
     * @field-labels
     *   id: Workflow ID
     *   env: Environment
     *   workflow: Workflow
     *   user: User
     *   status: Status
     *   time: Time
     *
     * @usage terminus workflow:info:operations <site_name> <workflow_id>
     *   Show the status of the workflow with ID <workflow_id> found on site <site_name>.
     * @usage terminus workflow:info:operations <site_name>
     *   Show the status of the most recent workflow found on site <site_name>.
     */
    public function status($site_id, $options = ['id' => null,])
    {
        $workflow_data = $this->getWorkflow($site_id, $options['id'])->serialize();
        unset($workflow_data['operations']);
        return new PropertyList($workflow_data);
    }
}
