<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;

class DeleteCommand extends TerminusCommand
{
    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = true;

    /**
     * Removes a machine token from the logged-in user's account
     *
     * @name machine-token:delete
     * @param string $machine_token_id The ID of the machine token to be deleted
     * @throws \Terminus\Exceptions\TerminusException
     * @aliases mt:delete
     *
     * @usage terminus machine-token:delete <machine_token_id>
     *   Removes the given machine token from the user's account
     */
    public function delete($machine_token_id) {
        $user = $this->session()->getUser();

        // Find the token. Will throw an exception if it doesn't exist.
        $machine_token = $user->machine_tokens->get($machine_token_id);
        $name = $machine_token->get('device_name');

        $this->log()->notice('Deleting {token} ...', ['token' => $name]);
        $response = $machine_token->delete();
        if ($response['status_code'] == 200) {
            $this->log()->notice('Deleted {token}!', ['token' => $name]);
        } else {
            throw new TerminusException('There was an problem deleting the machine token.');
        }
    }

}
