<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;

class ListCommand extends TerminusCommand
{
    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = true;

    /**
     * Lists the IDs and labels of machine tokens belonging to the logged-in user
     *
     * @name machine-token:list
     * @aliases machine-tokens mt:list mts
     *
     * @usage terminus machine-token:list
     *   Lists your user's machine tokens
     */
    public function listTokens() {

    }

}
