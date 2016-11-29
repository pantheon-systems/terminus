<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Pantheon\Terminus\Style\TerminusStyle;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Common\IO;

/**
 * Class TerminusCommand
 * @package Pantheon\Terminus\Commands
 */
abstract class TerminusCommand implements
    IOAwareInterface,
    LoggerAwareInterface,
    ConfigAwareInterface,
    SessionAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use IO {
        io as roboIo;
    }
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
     * Override Robo's IO function with our custom style.
     */
    protected function io()
    {
        if (!$this->io) {
            $this->io = new TerminusStyle($this->input(), $this->output());
        }
        return $this->io;
    }
}
