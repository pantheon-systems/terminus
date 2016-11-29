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
     * List the IDs and labels of SSH Keys belonging to the logged-in user
     *
     * @authorize
     *
     * @command ssh-key:list
     * @aliases ssh-keys
     *
     * @field-labels
     *   id: ID
     *   hex: Fingerprint
     *   comment: Description
     * @return RowsOfFields
     *
     * @usage terminus ssh-key:list
     *    Lists the saved SSH keys belonging to the logged-in user
     */
    public function listSSHKeys()
    {
        $ssh_keys = $this->session()->getUser()->getSshKeys()->all();

        $data = [];
        foreach ($ssh_keys as $id => $ssh_key) {
            $data[] = array(
                'id' => $ssh_key->id,
                'hex' => $ssh_key->getHex(),
                'comment' => $ssh_key->getComment(),
            );
        }
        if (count($data) == 0) {
            $this->log()->warning('You have no ssh keys.');
        }
        return new RowsOfFields($data);
    }
}
