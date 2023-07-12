<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class WaitWorkflowsCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class WaitWorkflowsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Waits for all workflows in a given environment and optionally attempt to do healthcheck on it.
     *
     * @authorize
     *
     * @command env:wait-workflows
     * @aliases env:ww
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option bool $healthcheck Whether to wait for the healthcheck to pass
     * @option int $timeout How long to wait for the workflows to finish
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Wakes <site>'s <env> environment by pinging it.
     */
    public function waitWorkflows($site_env, $options = [
        'healthcheck' => true,
        'timeout' => 300,
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSite($site_env);
        $env = $this->getEnv($site_env);

        $workflows = $site->getWorkflows()->setPaging(false)->fetch()->allByEnvironmentIdAndStatus(
            $env->id,
            'unfinished'
        );
        $this->log()->notice('Waiting for {number} workflow(s).', ['number' => count($workflows)]);

        $startWaiting = time();
        foreach ($workflows as $workflow) {
            $description = $workflow->get('description');
            $workflow->fetch();
            while ($workflow->isUnfinished()) {
                $this->log()->notice(
                    "Workflow '{description}' is running...",
                    ['description' => $description]
                );

                if (time() - $startWaiting > $options['timeout']) {
                    throw new TerminusException(
                        'Timeout waiting for workflows to finish. Please check the status of the workflows manually.'
                    );
                }
                sleep(5);
                $workflow->fetch();
            }
            // Abort if workflow failed.
            if ($workflow->isFailed()) {
                throw new TerminusException(
                    'Workflow failed. Please check the status of the workflows manually.'
                );
            }
            if ($workflow->isSuccessful()) {
                $this->log()->notice(
                    "Workflow '{description}' succeeded.",
                    ['description' => $description]
                );
            }
        }

        if ($options['healthcheck']) {
            $this->log()->notice('Running healthcheck on environment...');
            $env->wake();
        }

        $this->log()->notice('All done!');
    }
}
