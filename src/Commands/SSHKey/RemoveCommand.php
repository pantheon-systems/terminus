<?php

namespace Pantheon\Terminus\Commands\SSHKey;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\SSHKey
 */
class RemoveCommand extends TerminusCommand
{
    /**
     * Disassociates a SSH public key from the currently logged-in user.
     *
     * @authorize
     *
     * @command ssh-key:remove
     * @aliases ssh-key:rm
     *
     * @param string $ssh_key_id SSH public key ID
     *
     * @usage <ssh-key> Disassociates the SSH public key <ssh-key> from the currently logged-in user.
     */
    public function delete($ssh_key_id)
    {
        // Remove ':' to allow the id to be specified in ssh thumbnail format.
        $ssh_key_id = str_replace(':', '', $ssh_key_id);
        // Find the key. Will throw an exception if it doesn't exist.
        $key = $this->session()->getUser()->getSSHKeys()->get($ssh_key_id);
        $context = ['key' => $key->id,];

        $this->log()->notice('Deleting SSH key {key} ...', $context);
        $key->delete();
        $this->log()->notice('Deleted SSH key {key}!', $context);
    }
}
