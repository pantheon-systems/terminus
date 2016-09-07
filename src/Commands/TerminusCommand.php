<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Config;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Common\OutputAwareTrait;
use Robo\Common\InputAwareTrait;
use Terminus\Models\Auth;

abstract class TerminusCommand implements InputAwareInterface, LoggerAwareInterface, OutputAwareInterface
{
    use InputAwareTrait;
    use LoggerAwareTrait;
    use OutputAwareTrait;

    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = false;
    /**
     * @var Config
     */
    protected $config;

    /**
     * TerminusCommand constructor
     */
    public function __construct()
    {
        $this->config = new Config();
        if ($this->authorized) {
            $this->ensureLogin();
        }
    }

    /**
     * Returns a logger object for use
     *
     * @return LoggerInterface
     */
    protected function log()
    {
        return $this->logger;
    }

    /**
     * Logs the user in or errs
     *
     * @return void
     */
    private function ensureLogin() {
        $auth   = new Auth();
        $tokens = $auth->getAllSavedTokenEmails();
        if (!$auth->loggedIn()) {
            if (count($tokens) === 1) {
                $email = array_shift($tokens);
                $auth->logInViaMachineToken(compact('email'));
            } else if (!is_null($this->config->get('user')) && $email = $this->config->get('user')) {
                $auth->logInViaMachineToken(compact('email'));
            } else {
                $this->log()->error(
                  'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
        }
        return true;
    }
}
