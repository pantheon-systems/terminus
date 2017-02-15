<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CloneContentCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CloneContentCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Clones database/files from one environment to another environment.
     *
     * @authorize
     *
     * @command env:clone-content
     *
     * @param string $site_env Origin site & environment in the format `site-name.env`
     * @param string $target_env Target environment
     * @param array $options
     * @option bool $db-only Only clone database
     * @option bool $files-only Only clone files
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> <target_env> Clones database and files from <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --db-only Clones only the database from <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --files-only Clones only files from <site>'s <env> environment to <target_env> environment.
     */
    public function cloneContent($site_env, $target_env, array $options = ['db-only' => false, 'files-only' => false,])
    {
        if (!empty($options['db-only']) && !empty($options['files-only'])) {
            throw new TerminusException("You cannot specify both --db-only and --files-only");
        }

        list($site, $env) = $this->getUnfrozenSiteEnv($site_env);
        $from_name = $env->getName();
        $target = $site->getEnvironments()->get($target_env);
        $to_name = $target->getName();

        $tr = ['from' => $from_name, 'to' => $to_name, 'site' => $site->getName(),];
        if (!$env->isInitialized()) {
            throw new TerminusException(
                "{site}'s {from} environment cannot be cloned because it has not been initialized. Please run `env:deploy {site}.{from}` to initialize it.",
                $tr
            );
        }
        if (!$this->confirm('Are you sure you want to clone content from {from} to {to} on {site}?', $tr)) {
            return;
        }

        if (empty($options['db-only'])) {
            $workflow = $target->cloneFiles($from_name);
            $this->log()->notice(
                "Cloning files from {from_name} environment to {target_env} environment",
                compact(['from_name', 'target_env'])
            );
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        }

        if (empty($options['files-only'])) {
            $workflow = $target->cloneDatabase($from_name);
            $this->log()->notice(
                "Cloning database from {from_name} environment to {target_env} environment",
                compact(['from_name', 'target_env'])
            );
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        }
    }
}
