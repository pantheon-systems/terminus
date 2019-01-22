<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;

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
     * @return PropertyList
     *
     * @param string $site_id Site name
     * @option string $id Workflow UUID
     *
     * @usage <site> <workflow> Displays the status of <site>'s workflow <workflow>.
     * @usage <site> Displays the status of <site>'s  most recently created workflow.
     */
    public function status($site_id, $options = ['id' => null,])
    {
        $workflow = $this->getWorkflow($site_id, $options['id']);
        $workflow->unsetAttribute('operations');
        return $this->getPropertyList($workflow);
    }
}
