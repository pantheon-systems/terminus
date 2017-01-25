<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class LoginCommand
 * @package Pantheon\Terminus\Commands\Auth
 */
class LoginCommand extends TerminusCommand
{
    /**
     * Logs in a user to Pantheon.
     *
     * @command auth:login
     * @aliases login
     *
     * @option machine-token Grants access for a user and is saved for future logins
     * @option email Uses an existing machine token for this user
     *
     * @usage --machine-token=<machine_token> Logs in a user granted the machine token <machine_token>.
     * @usage Logs in a user with a previously saved machine token.
     * @usage --email=<email> Logs in a user with a previously saved machine token belonging to <email>.
     */
    public function logIn(array $options = ['machine-token' => null, 'email' => null,])
    {
        $tokens = $this->session()->getTokens();

        if (isset($options['machine-token']) && !is_null($token_string = $options['machine-token'])) {
            try {
                $token = $tokens->get($token_string);
            } catch (\Exception $e) {
                $this->log()->notice('Logging in via machine token.');
                $tokens->create($token_string);
            }
        } elseif (isset($options['email']) && !is_null($email = $options['email'])) {
            $token = $tokens->get($email);
        } elseif (count($all_tokens = $tokens->all()) == 1) {
            $token = array_shift($all_tokens);
            $this->log()->notice('Found a machine token for {email}.', ['email' => $token->get('email'),]);
        } else {
            if (count($all_tokens) > 1) {
                throw new TerminusException(
                    "Tokens were saved for the following email addresses:\n{tokens}\nYou may log in via `terminus"
                        . " auth:login --email=<email>`, or you may visit the dashboard to generate a machine"
                        . " token:\n{url}",
                    ['tokens' => implode("\n", $tokens->ids()), 'url' => $this->getMachineTokenCreationURL(),]
                );
            } else {
                throw new TerminusException(
                    "Please visit the dashboard to generate a machine token:\n{url}",
                    ['url' => $this->getMachineTokenCreationURL(),]
                );
            }
        }
        if (isset($token)) {
            $this->log()->notice('Logging in via machine token.');
            $token->logIn();
        }
    }

    /**
     * Generates the URL string for where to create a machine token
     *
     * @return string
     */
    private function getMachineTokenCreationURL()
    {
        return vsprintf(
            '%s://%s/machine-token/create/%s',
            [
                $this->config->get('dashboard_protocol'),
                $this->config->get('dashboard_host'),
                gethostname(),
            ]
        );
    }
}
