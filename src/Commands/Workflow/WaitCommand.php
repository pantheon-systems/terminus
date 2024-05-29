<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\WorkflowLog;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class AwaitCommand.
 *
 * @package Pantheon\Terminus\Commands\Workflow
 */
class WaitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Wait for a workflow to complete. This will automatically wait
     * for the most recent workflow to complete. to specify which workflow,
     * give the workflow ID or the git commit hash
     *
     * @command workflow:wait
     * @param $site_env {SiteName}.{env} from which to obtain workflows. Env is optional.
     * @option $workflow_id The ide of the workflow for which you want to wait.
     * @option $type The type of the workflow for which you want to wait.
     * @option $commit_hash The git commit hash for which you want to wait.
     * @option $start The start time of the workflow for which you want to wait.
     *
     * @throws TerminusNotFoundException
     */
    public function workflowWait(
        $site_env,
        $options = [
            'type' => null,
            'max' => 180,
            'id' => null,
            'commit_hash' => null,
            'start' => 0,
        ]
    ) {
        $site = $this->getSiteById($site_env);
        try {
            $env = $site->getEnvironments()->get($site_env);
        } catch (TerminusNotFoundException $e) {
            $env = null;
        }
        $this->waitForWorkflow($site, $env, $options);
    }

    /**
     * @param Site $site
     * @param Environment|null $env
     * @param array $options
     * @return void
     * @throws TerminusException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function waitForWorkflow(
        Site $site,
        Environment $env = null,
        array $options = [
            'type' => null,
            'max' => 180,
            'id' => null,
            'commit_hash' => null,
            'start' => 0,
        ]
    ) {
        $workflows = $site->getWorkflowLogs();
        if ($env) {
            $workflows = $workflows->filterForEnvironment($env);
        }
        $wfl = $workflows->findLatestFromOptionsArray($options);

        // You take it on faith
        // You take it to the heart
        // The waiting is the hardest part
        if ($wfl instanceof WorkflowLog) {
            return $wfl->waitForComplete($options['max']);
        }
        $this->log()->notice('No workflows found.');
    }
}
