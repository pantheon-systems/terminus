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
     * @usage Lists the currently logged-in user's machine tokens.
     */
    public function listTokens()
    {
        $data = $this->session()->getUser()->getMachineTokens()->serialize();
        if (count($data) == 0) {
            $this->log()->warning('You have no machine tokens.');
        }
        return new RowsOfFields($data);
    }
}
