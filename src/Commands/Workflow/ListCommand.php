<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Workflow
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List the workflows for a site
     *
     * @authorize
     *
     * @command workflow:list
     * @aliases workflows
     *
     * @field-labels
     *   id: Workflow ID
     *   env: Environment
     *   workflow: Workflow
     *   user: User
     *   status: Status
     *   time: Time
     * @return RowsOfFields
     *
     * @param string $site_id Site name to list the workflows of
     *
     * @usage terminus workflow:list <site>
     *   Lists the workflows for <site>
     */
    public function wfList($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getWorkflows()->fetch(['paged' => false]);
        $workflows = $site->getWorkflows()->all();

        $data = [];
        foreach ($workflows as $workflow) {
            foreach ($workflows as $workflow) {
                $workflow_data = $workflow->serialize();
                unset($workflow_data['operations']);
                $data[] = $workflow_data;
            }
            if (count($data) == 0) {
                $this->log()->warning(
                    'No workflows have been run on {site}.',
                    ['site' => $site->get('name')]
                );
            }
            return new RowsOfFields($data);
        }
    }
}
