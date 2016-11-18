<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class LogoutCommand
 * @package Pantheon\Terminus\Commands\Auth
 */
class LogoutCommand extends TerminusCommand
{

    /**
     * Log the currently logged-in user out of Pantheon
     *
     * @command auth:logout
     * @aliases logout
     *
     * @usage terminus auth:logout
     *   Logs you out of Pantheon by removing your saved session
     */
    public function logOut()
    {
        $this->session()->destroy();
        $this->log()->notice('You have been logged out of Pantheon.');
    }
}
