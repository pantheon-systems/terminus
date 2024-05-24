<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\TerminusModel;
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
     * @return TerminusModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    protected function getWorkflowLogs($site_id, $workflow_id = null): ?TerminusModel
    {
        $site = $this->getSiteById($site_id);
        if (!$site instanceof Site) {
            $this->log()->error('The site {site} was not found.', ['site' => $site_id,]);
            return null;
        }
        if ($workflow_id != null) {
            return $site->getWorkflowLogs()->findLatestFromOptionsArray(['id' => $workflow_id]);
        }
        $this->log()->notice('Showing latest workflow on {site}.', ['site' => $site->getName(),]);
        return $site->getWorkflowLogs()->latest();
    }
}
