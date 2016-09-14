<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Common\IO;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Terminus\Models\Auth;

abstract class TerminusCommand implements IOAwareInterface, LoggerAwareInterface, ConfigAwareInterface, SessionAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use IO;
    use SessionAwareTrait;

    /**
     * TerminusCommand constructor
     */
    public function __construct()
    {
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
    * @param $question
    * @return string
    */
    protected function confirm($question)
    {
        if ($this->input()->hasParameterOption(['--yes', '-y'])) {
            return true;
        }
        return $this->doAsk(new ConfirmationQuestion($this->formatQuestion($question . ' (y/n)'), false));
    }
}
