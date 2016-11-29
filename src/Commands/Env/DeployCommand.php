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
     * Deploy the dev environment to either test or live
     *
     * @authorize
     *
     * @command env:deploy
     * @aliases deploy
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`
     * @option string $sync-content If deploying test, copy database and files from live
     * @option string $cc Set to clear the cache after deploy
     * @option string $updatedb Set to run update.php after deploy (Drupal only)
     * @option string $note Set to add a custom deploy log message
     *
     * @usage terminus env:deploy <site>.<env>
     *   Deploy the dev environment of <site> to its <env> environment
     * @usage terminus env:deploy <site>.<env> --cc
     *   Deploy the dev environment of <site> to its <env> environment and clear its cache
     * @usage terminus env:deploy <site>.<env> --sync-content
     *   Deploy the dev environment of <site> to its <env> environment, copying the database and files from live
     * @usage terminus env:deploy <site>.<env> --updatedb
     *   Deploy the dev environment of <site> to its <env> environment and run Drupal's update.php
     * @usage terminus env:deploy <site>.<env> --note=<message>
     *   Deploy the dev environment of <site> to its <env> environment with the note <message>
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
