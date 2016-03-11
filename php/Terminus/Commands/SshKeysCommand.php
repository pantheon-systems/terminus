<?php

namespace Terminus\Commands;

use Terminus\Session;
use Terminus\Commands\TerminusCommand;

/**
 * Show, add, and delete SSH keys on your Pantheon account
 *
 * @command ssh-keys
 */
class SshKeysCommand extends TerminusCommand {

  /**
   * Instantiates object, ensures login
   *
   * @param array $options Options to construct the command object
   * @returns InstrumentsCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
  }

  /**
   * Show a list of your instruments on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $user     = Session::getUser();
    $ssh_keys = $user->ssh_keys->all();
    $data     = [];
    foreach ($ssh_keys as $id => $ssh_key) {
      $data[] = [
        'id'  => $ssh_key->get('id'),
        'hex' => $ssh_key->getHex(),
      ];
    }

    if (empty($data)) {
      $this->log()->info(
        'You do not have any SSH keys saved. Use {cmd} to add one.',
        ['cmd' => 'terminus ssh-keys add',]
      );
    }
    $this->output()->outputRecordList($data);
  }

}

