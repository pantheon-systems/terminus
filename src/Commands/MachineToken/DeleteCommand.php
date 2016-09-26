<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;

class DeleteCommand extends TerminusCommand
{
    /**
     * Removes a machine token from the logged-in user's account
     *
     * @authorized
     *
     * @command machine-token:delete
     * @aliases mt:delete
     *
     * @param string $machine_token_id The ID of the machine token to be deleted
     *
     * @usage terminus machine-token:delete <machine_token_id>
     *   Removes the given machine token from the user's account
     */
    public function delete($machine_token_id)
    {
        // Find the token. Will throw an exception if it doesn't exist.
        $machine_token = $this->session()->getUser()->machine_tokens->get($machine_token_id);
        $name = $machine_token->get('device_name');

        $this->log()->notice('Deleting {token} ...', ['token' => $name]);
        $machine_token->delete();
        $this->log()->notice('Deleted {token}!', ['token' => $name]);
    }
}
