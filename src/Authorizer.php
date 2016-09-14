<?php

namespace Pantheon\Terminus;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Consolidation\AnnotatedCommand\CommandError;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Terminus\Models\Auth;

class Authorizer implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;

    /**
     * Authorize the current user prior to running a command.  The
     * Annotated Commands hook manager will call this function during
     * the pre-validate phase of any command that has an 'authorize'
     * annotation.
     *
     * @hook pre-validate @authorize
     */
    public function ensureLogin()
    {
        $auth   = new Auth();
        $tokens = $auth->getAllSavedTokenEmails();
        if (!$auth->loggedIn()) {
            if (count($tokens) === 1) {
                $email = array_shift($tokens);
                $auth->logInViaMachineToken(compact('email'));
            } elseif (!is_null($this->config->get('user')) && $email = $this->config->get('user')) {
                $auth->logInViaMachineToken(compact('email'));
            } else {
                throw new \Exception(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
        }
    }
}
