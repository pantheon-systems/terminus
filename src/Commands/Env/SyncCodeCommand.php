<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SyncCodeCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class SyncCodeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Deploys code to the Test or Live environment.
     * Notes:
     *   - Deploying the Test environment will deploy code from the Dev environment.
     *   - Deploying the Live environment will deploy code from the Test environment.
     *
     * @authorize
     *
     * @command env:sync-code
     * @aliases sync-code
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.test Deploy code from <site>'s Dev environment to the Test environment.
     * @usage <site>.live Deploy code from <site>'s Test environment to the Live environment.
     * @usage <site>.test --sync-content Deploy code from <site>'s Dev environment to the Test environment and clone content from the Live environment to the Test environment.
     * @usage <site>.live --updatedb Deploy code from <site>'s Test environment to the Live environment and run Drupal's update.php.
     * @usage <site>.live --note=<message> Deploy code from <site>'s Test environment to the Live environment with the deploy log message <message>.
     */
    public function deploy(
        $site_env
    ) {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSite($site_env);
        $env = $this->getEnv($site_env);

        $params = [
            'converge' => true,
            'build_steps' => [
                'artifact_install' => true,
            ],
        ];

        $workflow = $env->syncCode($params);

        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
