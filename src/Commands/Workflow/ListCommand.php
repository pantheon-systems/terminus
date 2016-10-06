<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List workflows for a site
     *
     * @command workflow:list
     * @aliases workflows
     *
     * @param string $site_id Site name to list workflows for.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   id: Workflow ID
     *   env: Environment
     *   workflow: Workflow
     *   user: User
     *   status: Status
     *   time: Time
     *
     * @usage terminus workflow:list my-site
     *   List workflows for the site named `my-site`.
     */
    public function wfList($site_id)
    {
        $site = $this->getSite($site_id);
        $site->workflows->fetch(['paged' => false]);
        $workflows = $site->workflows->all();

        $data = [];
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
