<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DeployCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class DeployCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Deploys code to the Test or Live environment.
     * Notes:
     *   - Deploying the Test environment will deploy code from the Dev environment.
     *   - Deploying the Live environment will deploy code from the Test environment.
     *
     * @authorize
     *
     * @command env:deploy
     * @aliases deploy
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     * @option string $sync-content Clone database/files from Live environment when deploying Test environment
     * @option string $cc Clear caches after deploy
     * @option string $updatedb Run update.php after deploy (Drupal only)
     * @option string $note Custom deploy log message
     *
     * @usage terminus env:deploy <site>.test
     *   Deploy code from <site>'s Dev environment to the Test environment.
     * @usage terminus env:deploy <site>.live
     *   Deploy code from <site>'s Test environment to the Live environment.
     * @usage terminus env:deploy <site>.test --cc
     *   Deploy code from <site>'s Dev environment to the Test environment and clear caches on the Test environment.
     * @usage terminus env:deploy <site>.test --sync-content
     *   Deploy code from <site>'s Dev environment to the Test environment and clone content from the Live environment to the Test environment.
     * @usage terminus env:deploy <site>.live --updatedb
     *   Deploy code from <site>'s Test environment to the Live environment and run Drupal's update.php.
     * @usage terminus env:deploy <site>.live --note=<message>
     *   Deploy code from <site>'s Test environment to the Live environment with the deploy log message <message>.
     */
    public function deploy(
        $site_env,
        $options = ['sync-content' => false, 'note' => 'Deploy from Terminus', 'cc' => false, 'updatedb' => false,]
    ) {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');

        if ($env->isInitialized()) {
            if (!$env->hasDeployableCode()) {
                $this->log()->notice('There is nothing to deploy.');
                return;
            }

            $params = [
              'updatedb'    => (integer)$options['updatedb'],
              'clear_cache' => (integer)$options['cc'],
              'annotation'  => $options['note'],
            ];
            if ($env->id == 'test' && isset($options['sync-content']) && $options['sync-content']) {
                $params['clone_database'] = ['from_environment' => 'live',];
                $params['clone_files']    = ['from_environment' => 'live',];
            }
            $workflow = $env->deploy($params);
        } else {
            $workflow = $env->initializeBindings();
        }
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
