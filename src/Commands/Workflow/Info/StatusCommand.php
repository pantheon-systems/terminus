<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
class StatusCommand extends InfoBaseCommand
{
    /**
     * Displays the status of a workflow.
     *
     * @authorize
     *
     * @command workflow:info:status
     *
     * @field-labels
     *     id: Workflow ID
     *     env: Environment
     *     workflow: Workflow
     *     user: User
     *     status: Status
     *     started_at: Started At
     *     finished_at: Finished At
     *     time: Time Elapsed
     * @return PropertyList
     *
     * @param string $site_id Site name
     * @option string $id Workflow UUID
     *
     * @usage <site> <workflow> Displays the status of <site>'s workflow <workflow>.
     * @usage <site> Displays the status of <site>'s  most recently created workflow.
     */
    public function status($site_id, $options = ['id' => null,])
    {
        $workflow_data = $this->getWorkflow($site_id, $options['id'])->serialize();
        unset($workflow_data['operations']);
        return new PropertyList($workflow_data);
    }
}
