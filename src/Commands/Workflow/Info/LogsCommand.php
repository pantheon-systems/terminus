<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

class LogsCommand extends InfoBaseCommand
{
    /**
     * Show all details for a workflow including operations.
     *
     * @command workflow:info:logs
     *
     * @param string $site_id Name or ID of the site that the workflow is part of
     * @option string $id The UUID of a specific workflow to show
     *
     * @usage terminus workflow:info:logs <site_name> --id=<workflow_id>
     *   Show infor about the the workflow with ID <workflow_id> found on site <site_name>.
     * @usage terminus workflow:info:logs <site_name>
     *   Show info about the most recent workflow found on site <site_name>.
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
            if (!$output) {
                $this->log()->notice('Workflow operations did not contain any logs.');
            }
            return $output;
        } else {
            $this->log()->notice('Workflow does not contain any operations.');
        }
    }
}
