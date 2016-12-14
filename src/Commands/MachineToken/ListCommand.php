<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Pantheon\Terminus\Commands\TerminusCommand;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\MachineToken
 */
class ListCommand extends TerminusCommand
{
    /**
     * Lists the currently logged-in user's machine tokens.
     *
     * @authorize
     *
     * @command machine-token:list
     * @aliases machine-tokens mt:list mts
     *
     * @field-labels
     *   id: ID
     *   device_name: Device Name
     * @return RowsOfFields
     *
     * @usage terminus machine-token:list
     *   Lists the currently logged-in user's machine tokens.
     */
    public function listTokens()
    {
        $machine_tokens = $this->session()->getUser()->getMachineTokens()->all();
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
