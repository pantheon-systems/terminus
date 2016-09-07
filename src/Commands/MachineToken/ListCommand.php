<?php

namespace Pantheon\Terminus\Commands\MachineToken;

class ListCommand extends TerminusCommand
{
    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = true;

    /**
     * Lists the IDs and labels of machine tokens belonging to the logged-in user
     *
     * @usage terminus machine-token:list
     *   Lists your user's machine tokens
     */
    public function machineTokenList() {

    }

}
