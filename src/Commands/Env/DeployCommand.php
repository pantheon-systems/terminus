<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DeployCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Deploy the dev environment to test or live.
     *
     * @command env:deploy
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
     *
     * @option string $sync-content If deploying test, copy database and files from Live
     * @option string $cc Clear cache after deploy?
     * @option string $updatedb (Drupal only) run update.php after deploy
     * @option string $note Deploy log message
     *
     * @usage terminus env:deploy my-awesome-site.env-name
     *   Deploy from dev to test environment
     */
    public function deploy($site_env, $options = [
                                    'sync-content' => false,
                                    'note' => 'Deploy from Terminus',
                                    'cc' => false,
                                    'updatedb' => false,])
    {
        // @TODO: Switch this to the standard site.env input format?
        list(, $env) = $this->getSiteEnv($site_env, 'dev');

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
