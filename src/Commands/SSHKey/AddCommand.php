<?php

namespace Pantheon\Terminus\Commands\SSHKey;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\SSHKey
 */
class AddCommand extends TerminusCommand
{

    /**
     * Associates a SSH public key with the currently logged-in user.
     *
     * @authorize
     *
     * @command ssh-key:add
     *
     * @param string $file SSH public key filepath
     *
     * @usage <file_path> Associates the SSH public key at <file_path> with the currently logged-in user.
     */
    public function add($file)
    {
        $this->session()->getUser()->getSSHKeys()->addKey($file);
        $this->log()->notice('Added SSH key from file {file}.', compact('file'));
    }
}
