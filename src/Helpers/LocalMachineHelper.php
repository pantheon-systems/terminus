<?php

namespace Pantheon\Terminus\Helpers;

use Pantheon\Terminus\Exceptions\TerminusException;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Class ShellExecHelper
 *
 * A helper for executing commands on the local client. A wrapper for 'exec' and 'passthru'.
 *
 * @package Pantheon\Terminus\Helpers
 */
class LocalMachineHelper implements ConfigAwareInterface
{
    use ConfigAwareTrait;

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
     * @param boolean $useTty Whether to allocate a tty when running. Null to autodetect.
     * @return array The command output and exit_code
     */
    public function execInteractive($cmd, $callback = null, $useTty = null)
    {
        $process = $this->getProcess($cmd);
        // Set tty mode if the user is running terminus iteractively.
        if (function_exists('posix_isatty')) {
            if (!isset($useTty)) {
                $useTty = (posix_isatty(STDOUT) && posix_isatty(STDIN));
            }
            if (!posix_isatty(STDIN)) {
                $process->setInput(STDIN);
            }
        }
        $process->setTty($useTty);
        $process->start();
        $process->wait($callback);
        return ['output' => $process->getOutput(), 'exit_code' => $process->getExitCode(),];
    }

    /**
     * Returns a set-up filesystem object.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return new Filesystem();
    }

    /**
     * Returns a finder object
     *
     * @return Finder
     */
    public function getFinder()
    {
        return new Finder();
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
     * Reads to a file from the local system.
     *
     * @param string $filename Name of the file to read
     * @return string Content read from that file
     */
    public function readFile($filename)
    {
        return file_get_contents($this->fixFilename($filename));
    }

    /**
     * Writes to a file on the local system.
     *
     * @param string $filename Name of the file to write to
     * @param string $content Content to write to the file
     */
    public function writeFile($filename, $content)
    {
        $this->getFilesystem()->dumpFile($this->fixFilename($filename), $content);
    }

    /**
     * Accepts a filename/full path and localizes it to the user's system.
     *
     * @param string $filename
     * @return string
     */
    protected function fixFilename($filename)
    {
        $config = $this->getConfig();
        return $config->fixDirectorySeparators(str_replace('~', $config->get('user_home'), $filename));
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
