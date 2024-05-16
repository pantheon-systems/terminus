<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Models\WorkflowLog;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class InfoBaseCommand
 * @package Pantheon\Terminus\Commands\Workflow\Info
 */
abstract class InfoBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;


    /**
     * @param $site_id
     * @param $workflow_id
     * @return Workflow
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    protected function getWorkflow($site_id, $workflow_id = null): Workflow
    {
        $site = $this->getSiteById($site_id);
        $workflows = $site->getWorkflows();

        if (!is_null($workflow_id)) {
            return $workflows->get($workflow_id);
        }
        $this->log()->notice('Showing latest workflow on {site}.', ['site' => $site->getName(),]);
        return $workflows->latest();
    }

    /**
     * Get the WorkflowLogs collection
     *
     * @param string $site_id     UUID or name of the site to get a workflow of
     * @param string $workflow_id The UUID of a specific workflow to retrieve
     * @return Workflow
     */
    protected function getWorkflowLogs($site_id, $workflow_id = null): WorkflowLog
    {
        $site = $this->getSiteById($site_id);
        $wfl = $site->getWorkflowLogs();

        if (!is_null($workflow_id)) {
            return $wfl->get($workflow_id);
        }
        $this->log()->notice('Showing latest workflow on {site}.', ['site' => $site->getName(),]);
        return $wfl->latest();
    }
}
