<?php

namespace Pantheon\Terminus;

use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Consolidation\AnnotatedCommand\CommandError;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Terminus\Models\Auth;

class Authorizer implements LoggerAwareInterface, ConfigAwareInterface, SessionAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use SessionAwareTrait;

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
        $this->session()->ensureLogin();
    }
}
