<?php

namespace Pantheon\Terminus\Helpers;

use Pantheon\Terminus\Exceptions\TerminusException;

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
     * Execute the given command on the local machine and return the last line of output
     *
     * @param string $cmd The command to execute
     * @return string The last line of the output of the executed command.
     */
    public function exec($cmd) {
        return exec($cmd);
    }

    /**
     * Execute the given command on the local machine and return the exit code and raw output
     *
     * @param string $cmd The command to execute
     * @return array The command output and exit_code.
     */
    public function execRaw($cmd) {
        $exit_code = null;
        ob_start();
        passthru($cmd, $exit_code);
        $output = ob_get_clean();
        return ['output' => $output, 'exit_code' => $exit_code];
    }


    /**
     * Open the given URL in a browser on the local machine.
     *
     * @param $url
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function openUrl($url) {
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
            throw new TerminusException("Terminus is unable to open a browser on this OS");
        }
        $command = sprintf('%s %s', $cmd, $url);

        $this->exec($command);
    }
}
