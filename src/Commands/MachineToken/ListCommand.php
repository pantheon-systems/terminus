<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;


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
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     */
    public function listTokens($options = ['format' => 'table', 'fields' => '']) {
        $user = $this->session()->getUser();

        $machine_tokens = $user->machine_tokens->all();
        $data = array();
        foreach ($machine_tokens as $id => $machine_token) {
          $data[] = array(
            'id'          => $machine_token->id,
            'device_name' => $machine_token->get('device_name'),
          );
        }

        if (count($data) == 0) {
          $this->log()->warning('You have no machine tokens.');
        }

        // Return the output data.
        return new RowsOfFields($data);
    }

}
