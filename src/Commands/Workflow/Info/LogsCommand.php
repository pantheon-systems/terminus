<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

/**
 * Class LogsCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
class LogsCommand extends InfoBaseCommand
{
    /**
     * Show all details for a workflow, including operations
     *
     * @authorize
     *
     * @command workflow:info:logs
     *
     * @param string $site_id Name or UUID of the site that the workflow belongs to
     * @option string $id The UUID of a specific workflow to show
     *
     * @usage  <site_name> --id=<workflow>
     *   Shows info about the the workflow identified by <workflow> found on <site>
     * @usage  <site_name>
     *   Shows info about the most recent workflow found on <site>
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
