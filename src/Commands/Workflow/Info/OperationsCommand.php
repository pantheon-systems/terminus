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
     * Displays Quicksilver operation details of a workflow.
     *
     * @authorize
     *
     * @command workflow:info:operations
     *
     * @param string $site_id Site name
     * @option string $id Workflow UUID
     * @return RowsOfFields
     *
     * @field-labels
     *     type: Type
     *     result: Result
     *     duration: Duration
     *     description: Operation Description
     *
     * @usage <site> --id=<workflow> Displays the Quicksilver operation details of <site>'s workflow <workflow>.
     * @usage <site> Displays the Quicksilver operation details of <site>'s most recently created workflow.
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
