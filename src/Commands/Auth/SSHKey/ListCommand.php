<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\SSHKey\ListCommand
 */


namespace Pantheon\Terminus\Commands\Auth\SSHKey;


use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

class ListCommand extends TerminusCommand {

  /**
   * Lists the IDs and labels of SSH Keys belonging to the logged-in user
   *
   * @authorized
   *
   * @command auth:ssh-key:list
   * @aliases ssh-keys
   *
   * @return RowsOfFields
   *
   * @field-labels
   *   id: ID
   *   hex: Fingerprint
   *   comment: Description
   *
   * @example auth:ssh-key:list
   *
   */
  public function listSSHKeys($options = ['format' => 'table', 'fields' => '']) {
    $user = $user = $this->session()->getUser();
    $ssh_keys = $user->ssh_keys->all();

    $data = [];
    foreach ($ssh_keys as $id => $ssh_key) {
      $data[] = array(
        'id'      => $ssh_key->get('id'),
        'hex'     => $ssh_key->getHex(),
        'comment' => $ssh_key->getComment(),
      );
    }
    if (count($data) == 0) {
      $this->log()->warning('You have no ssh keys.');
    }
    // Return the output data.
    return new RowsOfFields($data);
  }

}