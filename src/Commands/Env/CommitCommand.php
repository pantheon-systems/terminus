<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CommitCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CommitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Commits code changes on a development environment.
     * Note: The environment's connection mode must be set to SFTP.
     *
     * @authorize
     *
     * @command env:commit
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $message Commit message
     * @option boolean $force Force a commit even if there doesn't seem to be anything to commit. This can lead to an empty commit.
     *
     * @usage <site>.<env> Commits code changes to <site>'s <env> environment with the default message.
     * @usage <site>.<env> --message=<message> Commits code changes to <site>'s <env> environment with the message <message>.
     */
    public function commit($site_env, $options = ['message' => 'Terminus commit.', 'force' => false])
    {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');

        if (empty($options['force'])) {
            $change_count = count((array)$env->diffstat());
            if ($change_count === 0) {
                $this->log()->warning('There is no code to commit.');
                return;
            }
        }

        if ($env->get('connection_mode') !== 'sftp') {
            $this->log()->warning('You can only commit code in an environment that is set to sftp mode.');
            return;
        }

        $this->processWorkflow($env->commitChanges($options['message']));
        $this->log()->notice('Your code was committed.');
    }
}
