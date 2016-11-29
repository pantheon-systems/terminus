<?php

namespace Pantheon\Terminus;

use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;

/**
 * Class Authorizer
 * @package Pantheon\Terminus
 */
class Authorizer implements ConfigAwareInterface, LoggerAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use LoggerAwareTrait;
    use SessionAwareTrait;

    /**
     * Authorize the current user prior to running a command.  The Annotated Commands hook manager will call this
     * function during the pre-validate phase of any command that has an 'authorize' annotation.
     * TODO: Currently this is not being triggered when commands using it are run.
     *
     * @hook pre-validate @authorize
     */
    public function ensureLogin()
    {
        if (!$this->session()->isActive()) {
            if (count($tokens = $this->session()->getTokens()->all()) == 1) {
                $token = array_shift($tokens);
            } elseif (!empty($email = $this->getConfig()->get('user'))) {
                $token = $this->session()->getTokens()->get($email);
            } else {
                throw new \Exception(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
            $token->logIn();
        }
    }
}
