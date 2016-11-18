<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class OperationsCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
class OperationsCommand extends InfoBaseCommand
{
    /**
     * Show operation data for a workflow
     *
     * @authorize
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
     * @usage terminus workflow:info:operations <site> --id=<workflow>
     *   Shows the operations of the workflow identified by <workflow> found on <site>
     * @usage terminus workflow:info:operations <site>
     *   Shows the operations of the most recent workflow found on <site>
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
