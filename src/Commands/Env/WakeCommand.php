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
        $attempt = 1;
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        while ($attempt <= $options['retry']) {
            $wakeStatus = $env->wake();
            if (empty($wakeStatus['success'])) {
                $this->log()->notice('{target} is not awake (attempt {attempt}/{max})...', [
                    'target' => $site_env,
                    'attempt' => $attempt,
                    'max' => $options['retry'],
                ]);
                // If there is any attempt remaining, sleep for the delay period.
                if ($attempt <= $options['retry'] - 1) {
                    $this->log()->notice('Sleeping for {delay} seconds...', ['delay' => $options['delay']]);
                    sleep($options['delay']);
                }
                $attempt++;
                continue;
            }
            break;
        }

        $this->log()->notice('OK >> {target} responded', $wakeStatus);
    }
}
