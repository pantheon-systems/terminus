<?php

namespace Pantheon\Terminus\Commands\Auth;

class WhoamiCommand extends TerminusCommand
{

    /**
     * Displays information about the user currently logged in
     *
     * @usage terminus auth:whoami
     *   Responds with the email of the logged-in user
     * @usage terminus auth:whoami -vvv
     *   Responds with the current session and user's data
     */
    public function authWhoami() {

    }

}
