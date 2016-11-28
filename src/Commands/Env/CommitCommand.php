<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CommitCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CommitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Commit code on an environment that is in SFTP mode
     *
     * @authorize
     *
     * @command env:commit
     *
     * @param string $site_env Site & environment to commit code on.
     * @option string $message Commit message
     *
     * @usage terminus env:commit <site>.<env>
     *   Commit changes to <site>'s <env> environment with the default message
     * @usage terminus env:commit <site>.<env> --message=<message>
     *   Commit changes to <site>'s <env> environment with the message <message>
     */
    public function commit($site_env, $options = ['message' => 'Terminus commit.'])
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');

        $change_count = count((array)$env->diffstat());
        if ($change_count === 0) {
            $this->log()->warning("There is no code to commit.");
            return;
        }

        $workflow = $env->commitChanges($options['message']);
        $workflow->wait();
        return $workflow->getMessage();
    }
}
