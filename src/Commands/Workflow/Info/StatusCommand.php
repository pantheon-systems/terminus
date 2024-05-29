<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
class StatusCommand extends InfoBaseCommand
{
    use StructuredListTrait;

    /**
     * Displays the status of a workflow.
     *
     * @authorize
     *
     * @command workflow:info:status
     *
     * @field-labels
     *     id: Workflow ID
     *     env: Environment
     *     workflow: Workflow
     *     user: User
     *     status: Status
     *     started_at: Started At
     *     finished_at: Finished At
     *     time: Time Elapsed
     * @param string $site_id Site name
     * @option string $id Workflow UUID
     *
     * @usage <site> <workflow> Displays the status of <site>'s workflow <workflow>.
     * @usage <site> Displays the status of <site>'s  most recently created workflow.
     * @return PropertyList
     *
     * @throws TerminusException
     */
    public function status($site_id, $options = ['id' => null,])
    {
        $wf = $this->getWorkflowLogs($site_id, $options['id']);
        if (empty($wf) && !empty($options['id'])) {
            $this->log()->notice('Workflow does not contain any operations.');
            throw new TerminusException('Unable to find workflow identified by {id}.', ['id' => $options['id']]);
        }
        return $this->getPropertyList($wf);
    }
}
