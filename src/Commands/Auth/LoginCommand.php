<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;
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
        $auth = new Auth();
        $tokens = $auth->getAllSavedTokenEmails();
        if (!is_null($token = $options['machine-token'])) {
            $auth->logInViaMachineToken(compact('token'));
            $this->log()->notice('Logging in via machine token.');
        } else if (!is_null($email = $options['email']) && !$auth->tokenExistsForEmail($email)) {
            $message = 'There are no saved tokens for %s.';
            throw new \Exception(vsprintf($message, compact('email')), 1);
        } else if (
        (
          (!is_null($email = $options['email']) || !empty($email = $this->config->get('user')))
          && $auth->tokenExistsForEmail($email)
        )
        ) {
            $auth->logInViaMachineToken(compact('email'));
            $this->log()->notice('Logging in via machine token.');
        } else if (is_null($options['email']) && (count($tokens) == 1)) {
            $email = array_shift($tokens);
            $this->log()->notice('Found a machine token for {email}.', compact('email'));
            $auth->logInViaMachineToken(compact('email'));
            $this->log()->notice('Logging in via machine token.');
        } else {
            if (count($tokens) > 1) {
                $message = "Tokens were saved for the following email addresses:\n"
                  . implode("\n", $tokens) . "\n You may log in via `terminus auth:login <email>` , or you may ";
            } else {
                $message = "Please ";
            }
            $message .= "visit the dashboard to generate a machine token:\n {$auth->getMachineTokenCreationUrl()}";
            throw new \Exception($message, 1);
        }
    }
}
