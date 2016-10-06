<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Auth;

class LoginCommand extends TerminusCommand
{

    /**
     * Logs a user into Pantheon
     *
     * @command auth:login
     * @aliases login
     *
     * @option machine-token A machine token to be saved for future logins
     * @usage terminus auth:login --machine-token=111111111111111111111111111111111111111111111
     *   Logs in the user granted machine token "111111111111111111111111111111111111111111111"
     * @usage terminus auth:login
     *   Logs in your user with a previously saved machine token
     * @usage terminus auth:login <email_address>
     *   Logs in your user with a previously saved machine token belonging to the account linked to the given email
     */
    public function logIn(array $options = ['machine-token' => null, 'email' => null,])
    {
        $session = $this->session();

        if (isset($options['machine-token']) && !is_null($token = $options['machine-token'])) {
            $session->logInViaMachineToken($token);
            $this->log()->notice('Logging in via machine token.');
            return;
        }

        $email = '';
        $emails = $session->getAllSavedTokenEmails();
        if (isset($options['email']) && !is_null($options['email'])) {
            $email = $options['email'];
        }
        else if (count($emails) == 1) {
            $email = reset($emails);
        }

        if ($email) {
            $session->logInViaSavedEmailMachineToken($email);
            $this->log()->notice('Found a machine token for {email}.', compact('email'));
            $this->log()->notice('Logging in via machine token.');
            return;
        }

        if (count($emails) > 1) {
            $message = "Tokens were saved for the following email addresses:\n"
              . implode("\n", $emails) . "\n You may log in via `terminus auth:login <email>` , or you may ";
        } else {
            $message = "Please ";
        }
        $message .= "visit the dashboard to generate a machine token:\n {url}";
        throw new TerminusException($message, ['url' => $session->getMachineTokenCreationUrl()]);
    }
}
