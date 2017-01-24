<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Lock
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enables HTTP basic authentication on the environment.
     * Note: HTTP basic authentication username and password are stored in plaintext.
     *
     * @authorize
     *
     * @command lock:enable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $username HTTP basic authentication username
     * @param string $password HTTP basic authentication password
     *
     * @usage <site>.<env> <username> <password> Enables HTTP basic authentication on <site>'s <env> environment with the username <username> and the password <password>.
     */
    public function enable($site_env, $username, $password)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->getLock()->enable(['username' => $username, 'password' => $password,]);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site}.{env} has been locked.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
