<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Console\Input\InputAwareInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Common\OutputAwareTrait;
use Robo\Common\InputAwareTrait;
use Terminus\Models\Auth;

abstract class TerminusCommand implements InputAwareInterface, OutputAwareInterface
{
    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = false;

    /**
     * TerminusCommand constructor
     *
     * @param Config $config Terminus configuration object
     */
    public function __construct($config) {
        $this->config = $config;
        if ($authorized) {
            $this->ensureLogin();
        }
    }

    private function ensureLogin() {
        $auth   = new Auth();
        $tokens = $auth->getAllSavedTokenEmails();
        if (!$auth->loggedIn()) {
            if (count($tokens) === 1) {
                $email = array_shift($tokens);
                $auth->logInViaMachineToken(compact('email'));
            } else if (!is_null($this->config->get('user'))
              && $email = $this->config->get('user')
            ) {
                $auth->logInViaMachineToken(compact('email'));
            } else {
                $message  = 'You are not logged in. Run `auth login` to ';
                $message .= 'authenticate or `help auth login` for more info.';
                $this->log()->error($message);
            }
        }
        return true;

    }

}
