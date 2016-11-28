<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class WakeCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class WakeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Ping a site to ensure it responds
     *
     * @authorize
     *
     * @command env:wake
     *
     * @param string $site_env The site and environment to wake
     *
     * @throws TerminusException
     *
     * @usage terminus env:wake <site>.<env>
     *    Pings the <env> environment of <site> to ensure it is active
     */
    public function wake($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $data = $env->wake();

        // @TODO: Move the exceptions up the chain to the `wake` function. (One env is ported over).
        if (empty($data['success'])) {
            throw new TerminusException('Could not reach {target}', $data);
        }
        if (empty($data['styx'])) {
            throw new TerminusException('Pantheon headers missing, which is not quite right.');
        }

        $this->log()->notice('OK >> {target} responded in {time}', $data);
    }
}
