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
     *
     * @usage <site> --id=<workflow> Displays the details of <site>'s workflow <workflow>.
     * @usage <site> Displays the details of <site>'s most recently created workflow.
     */
    public function logs($site_id, $options = ['id' => null,])
    {
        $operations = $this->getWorkflow($site_id, $options['id'])->operations();

        if (count($operations)) {
            $output = '';
            foreach ($operations as $operation) {
                if ($operation->has('log_output')) {
                    $output .= sprintf(
                        "\n------ %s ------\n%s\n",
                        $operation->description(),
                        $operation->get('log_output')
                    );
                }
            }
            if (empty($output)) {
                $this->log()->notice('Workflow operations did not contain any logs.');
            }
            return $output;
        } else {
            $this->log()->notice('Workflow does not contain any operations.');
        }
    }
}
