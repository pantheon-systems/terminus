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
     * @option int $retry Number of times to retry if the environment is not awake
     * @option int $delay Number of seconds to wait between retries
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Wakes <site>'s <env> environment by pinging it.
     */
    public function wake($site_env, $options = [
        'retry' => 3,
        'delay' => 3,
    ])
    {
        $attempt = 0;
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        while ($attempt < $options['retry']) {
            $wakeStatus = $env->wake();
            if (empty($wakeStatus['success'])) {
                $this->log()->notice('{target} is not awake, retrying in {delay} seconds', [
                    'target' => $site_env,
                    'delay' => $options['delay'],
                ]);
                sleep($options['delay']);
                $attempt++;
            } else {
                break;
            }
        }

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
