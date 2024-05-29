<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Workflow
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays the list of the workflows for a site. If the environment is provided, will filter
     * for that environment.
     *
     * @authorize
     *
     * @command workflow:list
     * @aliases wfl
     * @aliases workflows
     *
     * @option bool $all Requests are limited to the last 10 workflows run. This option has been deprecated
     *     but will not produce an error
     * @option bool $inProgress Requests are limited to workflows that are in progress
     * @option string $env Environment to filter by
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
     * @param string $site_id Site to list workflows for
     *
     * @usage <site> Displays the list of the workflows for <site>.
     * @usage <site>.<env> Displays the list of the workflows for <site> filtering for a specific environment.
     * @usage <site> --inProgress Displays the list of the workflows for <site> filtering for in-progress workflows.
     * @usage <site> --finished Displays the list of the workflows for <site> filtering for finished workflows.
     *
     * @return RowsOfFields
     *
     */
    public function wfList(
        $site_id,
        $options = [
            'all' => false,
            'inProgress' => false,
            'env' => null,
        ]
    ) {
        try {
            $site = $this->getSiteById($site_id);
            $wfl = $site->getWorkflowLogs();
            if (!empty($options['env'])) {
                $wfl->filter(function ($wf) use ($options) {
                    return $wf->get('environment') == $options['env'];
                });
            }
            return $this->getRowsOfFields($wfl, $options);
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
        }
    }
}
