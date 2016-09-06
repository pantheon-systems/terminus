<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;

class WhoamiCommand extends TerminusCommand
{

    /**
     * Displays information about the user currently logged in
     *
     * @name auth:whoami
     * @aliases whoami
     *
     * @usage terminus auth:whoami
     *   Responds with the email of the logged-in user
     * @usage terminus auth:whoami -vvv
     *   Responds with the current session and user's data
     */
    public function whoami() {

    }

}
