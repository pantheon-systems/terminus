<?php

namespace Pantheon\Terminus\Commands\Auth;

class LoginCommand extends TerminusCommand
{

    /**
     * Logs a user into Pantheon
     *
     * @option machine_token A machine token to be saved for future logins
     * @usage terminus auth:login --machine-token=111111111111111111111111111111111111111111111
     *   Logs in the user granted machine token "111111111111111111111111111111111111111111111"
     * @usage terminus auth:login
     *   Logs in your user with a previously saved machine token
     */
    public function authLogin($machine_token) {

    }

}
