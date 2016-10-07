<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Pantheon\Terminus\Commands\Workflow\Info\InfoBaseCommand;

class LogsCommand extends InfoBaseCommand
{
    /**
     * Show all details for a workflow including operations.
     *
     * @command workflow:info:logs
     *
     * @param string $site_id Site name that the workflow is part of.
     *
     * @option string $workflow-id ID of the workflow to show.
     * @option string $latest-with-logs Just show the latest workflow with logs.
     *
     * @usage terminus workflow:info:logs <site_name> <wf-id>
     *   Show the workflow with id <wf-id> found on site <site_name>.
     */
    public function logs($site_id, $options = ['latest-with-logs' => false, 'workflow-id' => ''])
    {
        $workflow = $this->getWorkflow($site_id, $options);
        if (!$workflow) {
            return;
        }
        $operations = $workflow->operations();
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
                $this->log()->notice("Workflow operations did not contain any logs.");
            }
            return $output;
        } else {
            $this->log()->notice("Workflow does not contain any operations.");
        }
    }
}
