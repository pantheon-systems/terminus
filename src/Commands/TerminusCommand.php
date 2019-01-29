<?php

namespace Pantheon\Terminus\Commands;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Pantheon\Terminus\Style\TerminusStyle;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\IO;

/**
 * Class TerminusCommand
 * @package Pantheon\Terminus\Commands
 */
abstract class TerminusCommand implements
    IOAwareInterface,
    LoggerAwareInterface,
    ConfigAwareInterface,
    ContainerAwareInterface,
    SessionAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
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

    /**
     * Confirm that the user wants to continue with the command.
     *
     * @deprecated 1.0.0 This is not the correct way to do this and will be removed in the future. Use with caution.
     *
     * @param $confirm_text
     * @param array $replacements
     * @return bool|string
     */
    protected function confirm($confirm_text, $replacements = [])
    {
        $input = $this->input();
        if ($input->hasOption('yes') && $input->getOption('yes')) {
            return true;
        }

        $tr = [];
        foreach ($replacements as $key => $val) {
            $tr['{' . $key . '}'] = $val;
        }
        $confirm_text = strtr($confirm_text, $tr);
        return $this->io()->confirm($confirm_text, false);
    }
}
