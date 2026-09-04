<?php

declare(strict_types=1);

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
    protected function log(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Override Robo's IO function with our custom style.
     */
    protected function io(): TerminusStyle
    {
        if (!$this->io) {
            $this->io = new TerminusStyle($this->input(), $this->output());
        }
        return $this->io;
    }

    /**
     * Confirm that the user wants to continue with the command.
     *
     * @param string $confirm_text
     * @param array $replacements
     * @return bool
     */
    protected function confirm(string $confirm_text, array $replacements = []): bool
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
