<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Collections\Sites;

class DeployCommand extends TerminusCommand
{

    /**
     * Deploy the dev environment to test or live.
     *
     * @command env:deploy
     *
     * @param string $env Environment to deploy to
     *
     * @option string $site Site to deploy from
     * @option string $sync-content If deploying test, copy database and files from Live
     * @option string $cc Clear cache after deploy?
     * @option string $updatedb (Drupal only) run update.php after deploy
     * @option string $note Deploy log message
     *
     * @usage terminus env:deploy test --site=my-site-1
     *   Deploy from dev to test environment
     */
    public function deploy($env, $options = [
                                    'site' => '',
                                    'sync-content' => false,
                                    'note' => 'Deploy from Terminus',
                                    'cc' => false,
                                    'updatedb' => false])
    {
        $sites = new Sites();
        $site = $sites->get($options['site']);
        $env  = $site->environments->get($env);

        if (!$env->hasDeployableCode()) {
            $this->log()->info('There is nothing to deploy.');
            return;
        }

        $params = [
            'updatedb' => (integer)$options['updatedb'],
            'clear_cache' => (integer)$options['cc'],
            'annotation' => $options['note'],
        ];
        if ($env->id == 'test' && isset($options['sync-content'])) {
            $params['clone_database'] = ['from_environment' => 'live',];
            $params['clone_files'] = ['from_environment' => 'live',];
        }

        $workflow = $env->deploy($params);
        $workflow->wait();
        $this->workflowOutput($workflow, ['failure' => 'Deployment failed.',]);
    }
}
