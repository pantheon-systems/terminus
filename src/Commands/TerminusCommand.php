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
}
