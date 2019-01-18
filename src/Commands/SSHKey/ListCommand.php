<?php

namespace Pantheon\Terminus\Commands\SSHKey;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\SSHKey
 */
class ListCommand extends TerminusCommand
{
    use StructuredListTrait;

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
        return $this->getRowsOfFields($this->session()->getUser()->getSSHKeys());
    }
}
