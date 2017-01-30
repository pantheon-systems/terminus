<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;

class DeleteCommand extends TerminusCommand
{
    /**
     * Deletes a currently logged-in user's machine token.
     *
     * @authorize
     *
     * @command machine-token:delete
     * @aliases mt:delete
     *
     * @param string $machine_token_id Machine Token ID
     *
     * @usage <machine_token> Deletes the currently logged-in user's machine token, <machine_token>.
     */
    public function delete($machine_token_id)
    {
        // Find the token. Will throw an exception if it doesn't exist.
        $machine_token = $this->session()->getUser()->getMachineTokens()->get($machine_token_id);
        $name = $machine_token->get('device_name');

        if (!$this->confirm('Are you sure you want to delete this machine token?')) {
            return;
        }

        $this->log()->notice('Deleting {token} ...', ['token' => $name]);
        $machine_token->delete();
        $this->log()->notice('Deleted {token}!', ['token' => $name]);
    }
}
