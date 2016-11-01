<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class WakeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Pings a site to ensure it responds
     *
     * @authorized
     *
     * @command env:wake
     *
     * @param string $site_env The site and environment to wake.
     *
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function wakeEnv($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $data = $env->wake();

        // @TODO: Move the excetions up the chain to the `wake` function. (One env is ported over).
        if (empty($data['success'])) {
            throw new TerminusException('Could not reach {target}', $data);
        }
        if (empty($data['styx'])) {
            throw new TerminusException('Pantheon headers missing, which is not quite right.');
        }

        $this->log()->notice('OK >> {target} responded in {time}', $data);
    }
}
