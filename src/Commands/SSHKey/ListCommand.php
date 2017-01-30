<?php

namespace Pantheon\Terminus\Commands\SSHKey;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\SSHKey
 */
class ListCommand extends TerminusCommand
{

    /**
     * Displays the list of SSH public keys associated with the currently logged-in user.
     *
     * @authorize
     *
     * @command ssh-key:list
     * @aliases ssh-keys
     *
     * @field-labels
     *     id: ID
     *     hex: Fingerprint
     *     comment: Description
     * @return RowsOfFields
     *
     * @usage Displays the list of SSH public keys associated with the currently logged-in user.
     */
    public function listSSHKeys()
    {
        $data = $this->session()->getUser()->getSSHKeys()->serialize();
        if (count($data) == 0) {
            $this->log()->warning('You have no ssh keys.');
        }
        return new RowsOfFields($data);
    }
}
