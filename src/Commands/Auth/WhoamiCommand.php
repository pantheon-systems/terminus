<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Models\Auth;

class WhoamiCommand extends TerminusCommand
{
    /**
     * Displays information about the user currently logged in
     *
     * @command auth:whoami
     * @aliases whoami
     *
     * @usage terminus auth:whoami
     *   Responds with the email of the logged-in user
     * @usage terminus auth:whoami -vvv
     *   Responds with the current session and user's data
     * @return string
     */
    public function whoAmI()
    {
        $auth = new Auth();
        if ($auth->loggedIn()) {
            $user = $this->session()->getUser();
            $this->log()->debug(print_r($user->fetch()->serialize(), true));
            return $user->get('email');
        } else {
            $this->log()->notice('You are not logged in.');
            return null;
        }
    }
}
