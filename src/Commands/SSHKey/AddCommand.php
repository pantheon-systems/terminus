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
     * Add a SSH key to the logged-in user's account
     *
     * @authorize
     *
     * @command ssh-key:add
     *
     * @param string $file The path to the SSH public key file to use
     *
     * @usage terminus ssh-key:add <file_path>
     *   Adds the public key at the given file path <file_path> to your account
     */
    public function add($file)
    {
        $this->session()->getUser()->getSSHKeys()->addKey($file);
        $this->log()->notice('Added SSH key from file {file}.', compact('file'));
    }
}
