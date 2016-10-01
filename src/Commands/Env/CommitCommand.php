<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class CommitCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Commit code on an environment that is in SFTP mode.
     *
     * @command env:commit
     *
     * @param string $site_env Site & environment to commit code on.
     *
     * @option string $message Commit message
     *
     * @usage terminus env:commit my-site.dev --message="My code changes"
     *   Commit changes to the `dev` environment for site `my-site`.
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
