<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Workflow\Info\InfoBaseCommand;

class OperationsCommand extends InfoBaseCommand
{
    /**
     * Show operation data for a workflow.
     *
     * @command workflow:info:operations
     *
     * @param string $site_id Site name that the workflow is part of.
     *
     * @option string $workflow-id ID of the workflow to show.
     * @option string $latest-with-logs Just show the latest workflow with logs.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   type: Type
     *   description: Operation Description
     *   result: Result
     *   duration: Duration
     *
     * @usage terminus workflow:info:operations <site_name> <wf-id>
     *   Show the workflow with id <wf-id> found on site <site_name>.
     */
    public function operations($site_id, $options = ['latest-with-logs' => false, 'workflow-id' => ''])
    {
        $workflow = $this->getWorkflow($site_id, $options);
        if (!$workflow) {
            return;
        }
        $operations = $workflow->operations();
        if (count($operations)) {
            $operations_data = array_map(
                function ($operation) {
                    $operation_data = $operation->serialize();
                    unset($operation_data['id']);
                    unset($operation_data['log_output']);
                    return $operation_data;
                },
                $operations
            );
            return new RowsOfFields($operations_data);
        } else {
            $this->log()->notice("Workflow does not contain any operations.");
        }
    }
}
