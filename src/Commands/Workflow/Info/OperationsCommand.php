<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class OperationsCommand extends InfoBaseCommand
{
    /**
     * Show operation data for a workflow.
     *
     * @command workflow:info:operations
     *
     * @param string $site_id Name or ID of the site that the workflow is part of
     * @option string $id UUID of the workflow to show
     * @return RowsOfFields
     *
     * @field-labels
     *   type: Type
     *   description: Operation Description
     *   result: Result
     *   duration: Duration
     *
     * @usage terminus workflow:info:operations <site_name> <workflow_id>
     *   Show the operations of the workflow with ID <workflow_id> found on site <site_name>.
     * @usage terminus workflow:info:operations <site_name>
     *   Show the operations of the most recent workflow found on site <site_name>.
     */
    public function operations($site_id, $options = ['id' => null,])
    {
        $operations = $this->getWorkflow($site_id, $options['id'])->operations();
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
            $this->log()->notice('Workflow does not contain any operations.');
        }
    }
}
