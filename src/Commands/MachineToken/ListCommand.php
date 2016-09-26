<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class ListCommand extends TerminusCommand
{
    /**
     * Lists the IDs and labels of machine tokens belonging to the logged-in user
     *
     * @authorized
     *
     * @name machine-token:list
     * @aliases machine-tokens mt:list mts
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   id: ID
     *   device_name: Device Name
     *
     * @usage terminus machine-token:list
     *   Lists your user's machine tokens
     */
    public function listTokens()
    {

        $machine_tokens = $this->session()->getUser()->machine_tokens->all();
        $data = array();
        foreach ($machine_tokens as $id => $machine_token) {
            $data[] = array(
                'id' => $machine_token->id,
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
