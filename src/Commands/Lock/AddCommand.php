<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Locks an environment with a username and password
     * Note: The username and password are stored in plaintext format on the server.
     *
     * @authorized
     *
     * @command lock:add
     *
     * @param string $site_env The site/environment to lock
     * @param string $username Username for the environment lock
     * @param string $password Password for the environment lock
     */
    public function add($site_env, $username, $password)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->getLock()->add(['username' => $username, 'password' => $password,]);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site}.{env} has been locked.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
