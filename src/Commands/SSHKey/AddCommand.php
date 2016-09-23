<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\SSHKey\AddCommand
 */


namespace Pantheon\Terminus\Commands\SSHKey;

use Pantheon\Terminus\Commands\TerminusCommand;

class AddCommand extends TerminusCommand
{

    /**
     * Add a SSH key to your account
     *
     * @authorized
     *
     * @command ssh-key:add
     *
     * @param string $file The path to the SSH public key file to use
     *
     * @usage terminus ssh-key:add ~/.ssh/id_rsa.pub
     *   Adds the public key at the given file path to your account
     */
    public function add($file)
    {
        $user = $this->session()->getUser();
        $user->ssh_keys->addKey($file);
        $this->log()->notice('Added SSH key from file {file}.', compact('file'));
    }
}
