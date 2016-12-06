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
     * Logs out the currently logged-in user.
     *
     * @command auth:logout
     * @aliases logout
     *
     * @usage terminus auth:logout
     *     Logs out of Pantheon and removes saved session.
     */
    public function logOut()
    {
        $this->session()->destroy();
        $this->log()->notice('You have been logged out of Pantheon.');
    }
}
