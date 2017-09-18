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
        $data = $this->getWorkflow($site_id, $options['id'])->getOperations()->serialize();
        if (empty($data)) {
            $this->log()->notice('Workflow does not contain any operations.');
        }
        return new RowsOfFields($data);
    }
}
