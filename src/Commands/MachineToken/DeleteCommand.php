<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;

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
     * @aliases mt:delete
     *
     * @param machine_token_id The ID of the machine token to be deleted
     * @usage terminus machine-token:delete <machine_token_id>
     *   Removes the given machine token from the user's account
     */
    public function delete($machine_token_id) {

    }

}
