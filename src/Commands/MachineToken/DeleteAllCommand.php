<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;

class DeleteAllCommand extends TerminusCommand
{

  /**
   * Delete all stored machine tokens and log out.
   *
   * @command machine-token:delete-all
   * @aliases mt:delete-all
   **
   * @usage Deletes all of the stored machine tokens belonging to the current user.
   */
    public function deleteAll()
    {
        $tokens  = $this->session()->getTokens();
        $tokens->deleteAll();
        $this->session()->destroy();
        $this->log()->notice('Your saved machine tokens have been deleted and you have been logged out.');
    }
}
