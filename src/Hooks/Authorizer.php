<?php

namespace Pantheon\Terminus\Hooks;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class Authorizer
 * @package Pantheon\Terminus\Hooks
 */
class Authorizer implements ConfigAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use SessionAwareTrait;

    /**
     * Authorize the current user prior to running a command. The Annotated Commands hook manager will call this
     * function during the pre-validate phase of any command that has an 'authorize' annotation.
     *
     * @hook pre-init @authorize
     *
     * @throws TerminusException
     */
    public function ensureLogin()
    {
        if (!$this->session()->isActive()) {
            $tokens_obj = $this->session()->getTokens();
            if (count($tokens = $tokens_obj->all()) == 1) {
                $token = array_shift($tokens);
            } elseif (!empty($email = $this->getConfig()->get('user'))) {
                $token = $tokens_obj->get($email);
            } else {
                throw new TerminusException(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
            $token->logIn();
        }
    }
}
