<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoBaseCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
abstract class InfoBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Get the Workflow object
     *
     * @param string $site_id     UUID or name of the site to get a workflow of
     * @param string $workflow_id The UUID of a specific workflow to retrieve
     * @return Workflow
     */
    protected function getWorkflow($site_id, $workflow_id = null)
    {
        $site = $this->getSite($site_id);
        $workflows = $site->getWorkflows()->setPaging(false)->fetch()->all();

        if (!is_null($workflow_id)) {
            $workflow = $site->getWorkflows()->get($workflow_id);
        } else {
            $workflow = array_shift($workflows);
            $this->log()->notice('Showing latest workflow on {site}.', ['site' => $site->getName(),]);
        }
        $workflow->fetchWithLogs();
        return $workflow;
    }
}
