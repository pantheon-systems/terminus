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
     * Displays the list of the workflows for a site.
     *
     * @authorize
     *
     * @command workflow:list
     * @aliases workflows
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
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @usage <site>
     *   Displays the list of the workflows for <site>.
     */
    public function wfList($site_id)
    {
        $site = $this->getSite($site_id);
        $workflows = $site->getWorkflows()->setPaging(false)->fetch()->serialize();

        if (count($workflows) == 0) {
            $this->log()->warning(
                'No workflows have been run on {site}.',
                ['site' => $site->get('name')]
            );
        }
        return new RowsOfFields($workflows);
    }
}
