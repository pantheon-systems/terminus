<?php

namespace Pantheon\Terminus\Commands\MachineToken;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\MachineToken
 */
class ListCommand extends TerminusCommand
{
    use StructuredListTrait;

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
        return $this->getRowsOfFields($this->session()->getUser()->getMachineTokens());
    }
}
