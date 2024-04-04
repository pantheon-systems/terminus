<?php

namespace Pantheon\Terminus\Helpers\Traits;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Psr\Log\LoggerInterface;

trait WaitForWakeTrait
{
    /**
     * Waits for the site to wake up.
     *
     * @param Environment $env
     * @throws \Exception
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
            // If we've waited more than 25 seconds, then something is wrong.
            if ($waits > 5) {
                throw new TerminusException('Could not confirm that the site is working; there might be a problem.');
            }
            sleep(1);
            $waits++;
        } while (true);
        $logger->notice('Your site has been created successfully!');
    }
}
