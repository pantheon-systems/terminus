<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class WakeCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class WakeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Wakes the environment by pinging it.
     * Note: Development environments and Sandbox sites will automatically sleep after a period of inactivity.
     *
     * @authorize
     *
     * @command env:wake
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Wakes <site>'s <env> environment by pinging it.
     */
    public function wake($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);
        $wakeStatus = $env->wake();

        // @TODO: Move the exceptions up the chain to the `wake` function. (One env is ported over).
        if (empty($wakeStatus['success'])) {
            throw new TerminusException('Could not reach {target}', $wakeStatus);
        }
        if (empty($wakeStatus['styx'])) {
            throw new TerminusException('Pantheon headers missing, which is not quite right.');
        }

        $this->log()->notice('OK >> {target} responded', $wakeStatus);
    }
}
