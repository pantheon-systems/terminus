<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

/**
 * Class LogsCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
class LogsCommand extends InfoBaseCommand
{
    /**
     * Displays the details of a workflow including Quicksilver operation logs.
     *
     * @authorize
     *
     * @command workflow:info:logs
     *
     * @param string $site_id Site name
     * @option string $id Workflow UUID
     * @return string
     *
     * @usage <site> --id=<workflow> Displays the details of <site>'s workflow <workflow>.
     * @usage <site> Displays the details of <site>'s most recently created workflow.
     */
    public function logs($site_id, $options = ['id' => null,])
    {
        $workflow_ops = $this->getWorkflow($site_id, $options['id'])->getOperations();
        $operations = $workflow_ops->all();
        $log_operations = array_filter(
            $operations,
            function ($op) {
                return !is_null($got_output = $op->get('log_output')) && $got_output;
            }
        );

        if (empty($operations)) {
            $this->log()->notice('Workflow does not contain any operations.');
        } else if (empty($log_operations)) {
            $this->log()->notice('Workflow operations did not contain any logs.');
        }

        $logs = [];
        foreach ($log_operations as $op) {
            $logs[] = "$op";
        }
        return implode("\n", $logs);
    }
}
