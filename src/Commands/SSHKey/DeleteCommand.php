<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\Auth\SSHKey\DeleteCommand
 */


namespace Pantheon\Terminus\Commands\SSHKey;


use Pantheon\Terminus\Commands\TerminusCommand;

class DeleteCommand extends TerminusCommand
{
  /**
   * Removes an SSH key from the logged-in user's account
   *
   * @authorized
   *
   * @command ssh-key:delete
   * @aliases ssh-key:rm
   *
   * @param string $ssh_key_id The ID of the machine token to be deleted
   *
   * @usage terminus ssh-key:delete <ssh-key-id>
   *   Removes the SSH key with the specified id from the user's account
   */
  public function delete($ssh_key_id) {
    $user = $this->session()->getUser();

    // Remove ':' to allow the id to be specified in ssh thumbnail format.
    $ssh_key_id = str_replace(':', '', $ssh_key_id);
    // Find the key. Will throw an exception if it doesn't exist.
    $key = $user->ssh_keys->get($ssh_key_id);
    $name = $key->get('id');

    $this->log()->notice('Deleting SSH key {key} ...', ['key' => $name]);
    $key->delete();
    $this->log()->notice('Deleted SSH key {key}!', ['key' => $name]);
  }

}
