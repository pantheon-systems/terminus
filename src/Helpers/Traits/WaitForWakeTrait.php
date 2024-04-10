<?php

namespace Pantheon\Terminus\Helpers\Traits;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Psr\Log\LoggerInterface;

trait WaitForWakeTrait
{
    use ConfigAwareTrait;

    /**
     * Waits for the site to wake up.
     *
     * @param Environment $env
     * @param LoggerInterface $logger
     * @throws TerminusException
     */
    public function waitForWake(Environment $env, LoggerInterface $logger)
    {
        $waits = 0;
        do {
            $woke = $env->wake();
            if (($woke['success'] ?? false) === true) {
                break;
            }
            // if success is empty, then the site is still waking up.
            // Allow user to set the number of retries if the site is still waking up.
            // Default should be 25 times, once per second.
            if ($waits > $this->getConfig()->get("wait_for_wake_repeat", 25)) {
                throw new TerminusException('Could not confirm that the site is working; there might be a problem.');
            }
            sleep(1);
            $waits++;
        } while (true);
        $logger->notice(sprintf('%s => %s has been created successfully and is available for use.', $env->getSite()->getName(), $env->get('name')));
    }
}
