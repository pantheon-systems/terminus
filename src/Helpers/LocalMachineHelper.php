<?php

namespace Pantheon\Terminus\Helpers;

use Pantheon\Terminus\Exceptions\TerminusException;
use Symfony\Component\Process\Process;

/**
 * Class ShellExecHelper
 *
 * A helper for executing commands on the local client. A wrapper for 'exec' and 'passthru'.
 *
 * @package Pantheon\Terminus\Helpers
 */
class LocalMachineHelper
{
    /**
     * @var integer The number of seconds to wait on a command until it times out
     */
    const TIMEOUT = 3600;

    /**
     * Executes the given command on the local machine and return the exit code and output.
     *
     * @param string $cmd The command to execute
     * @return array The command output and exit_code
     */
    public function exec($cmd)
    {
        $process = $this->getProcess($cmd);
        $process->run();
        return ['output' => $process->getOutput(), 'exit_code' => $process->getExitCode(),];
    }

    /**
     * Executes a buffered command.
     *
     * @param string $cmd The command to execute
     * @param callable $callback A function to run while waiting for the process to complete
     * @return array The command output and exit_code
     */
    public function execInteractive($cmd, $callback)
    {
        $process = $this->getProcess($cmd);
        $process->setTty(true);
        $process->start();
        $process->wait($callback);
        return ['output' => $process->getOutput(), 'exit_code' => $process->getExitCode(),];
    }

    /**
     * Opens the given URL in a browser on the local machine.
     *
     * @param $url The URL to be opened
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function openUrl($url)
    {
        // Otherwise attempt to launch it.
        $cmd = '';
        switch (php_uname('s')) {
            case 'Linux':
                $cmd = 'xdg-open';
                break;
            case 'Darwin':
                $cmd = 'open';
                break;
            case 'Windows NT':
                $cmd = 'start';
                break;
        }
        if (!$cmd) {
            throw new TerminusException('Terminus is unable to open a browser on this OS.');
        }
        $command = sprintf('%s %s', $cmd, $url);

        $this->getProcess($command)->run();
    }

    /**
     * Returns a set-up process object.
     *
     * @param string $cmd The command to execute
     * @return Process
     */
    protected function getProcess($cmd)
    {
        $process = new Process($cmd);
        $process->setTimeout(self::TIMEOUT);
        return $process;
    }
}
