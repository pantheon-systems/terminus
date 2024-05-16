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
     * @param Environment $env
     * @param array $options
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
        // Start out with whatever the latest workflow is
        $wfl = $workflows->latest();

        // if any of the options are set, descend into the logic to find the workflow
        if (
            $options['type'] != null
            || $options['id'] != null
            || $options['commit_hash'] != null
            || $options['start'] > 0
        ) {
            switch (true) {
                // 1. if there are workflows running in multiple environments
                // make sure you have the latest one for given environment
                case ($env instanceof Environment && $wfl->get("environment") !== $env->id):
                    $workflows = $workflows->filter(function ($workflow) use ($env) {
                        return $workflow->get("environment") === $env->id;
                    });
                    // Now reset the to the latest workflow in the environment
                    $wfl = $workflows->latest();
                    // we don't want to break here because id, type or commit_hash might be set

                // if we have a match, then break
                case ($wfl->get('type') === $options['type']):
                case ($wfl->get('id') === $options['id']):
                case ($wfl->get('commit_hash') === $options['commit_hash']):
                case ($wfl->get('started_at') === $options['start']):
                    // these are the droids you're looking for
                    break;

                    // It's not a match, so let's try to find the workflow

                    // 1. Attempt to find workflow by id
                    // if the workflow id is set and the latest workflow is not the required workflow,
                    // then find the workflow by id
                case ($options['id']):
                    $wfl = $workflows->findByProperty('id', $options['id']);
                    break;

                    // 2. Attempt to find workflow by type
                    // if the latest workflow is not of the required type,
                    // and the type is set, then find the workflow by type
                case ($options['type']):
                    $wfl = $workflows->findByProperty('type', $options['type']);
                    break;

                    // 3. Attempt to find workflow by commit hash
                    // if the commit hash is set and the latest workflow is not the required workflow,
                    // then find the workflow by commit hash
                case ($options['commit_hash']):
                    $wfl = $workflows->findByProperty('commit_hash', $options['commit_hash']);
                    break;

                    // 4. Attempt to find workflow by start time
                    // if the start time is set and the latest workflow is not the required workflow,
                    // then find the workflow by start time
                    // This is the least preferred choice because of inaccuracies in the start time
                    // it remains here only to provide compatibility with the previous version
                case ($options['start'] > 0):
                    $wfl = $workflows->findByProperty('started_at', $options['start']);
                    break;

                default:
                    throw new TerminusException('Workflow not found.');
            }
        }

        if (!$wfl instanceof WorkflowLog) {
            // We tried to find a match above, but it returned null.
            throw new TerminusException('Workflow not found.');
        }


        // You take it on faith
        // You take it to the heart
        // The waiting is the hardest part
        while (!$wfl->isFinished()) {
            sleep($this->getConfigValue("refresh_workflow_delay"));
            $wfl->fetch();
        }
    }
}
