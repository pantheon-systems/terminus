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
     * Logs out the currently logged-in user and deletes any saved machine tokens.
     *
     * @command auth:logout
     * @aliases logout
     *
     * @usage Logs out of Pantheon and removes saved session and machine tokens.
     */
    public function logOut()
    {
        $this->session()->getTokens()->deleteAll();
        $this->session()->destroy();
        $this->log()->notice('Your saved machine tokens have been deleted and you have been logged out.');
    }
}
