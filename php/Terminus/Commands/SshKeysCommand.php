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
   * @var User
   */
  private $user;

  /**
   * Instantiates object, ensures login
   *
   * @param array $options Options to construct the command object
   * @returns InstrumentsCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->user = Session::getUser();
  }

  /**
   * Show a list of your SSH keys on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $ssh_keys = $this->user->ssh_keys->all();
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

  /**
   * Add a SSH key to your account
   *
   * [--file=<filename>]
   * : The path specific SSH public key file to use
   */
  public function add($args, $assoc_args) {
    $file = $this->input()->fileName(
      [
        'args'    => $assoc_args,
        'dir'     => getenv('HOME') . '/.ssh',
        'message' => 'Please select your public SSH key file.',
        'regex'   => '~(.*.pub)~',
      ]
    ); 
    $this->user->ssh_keys->addKey($file); 
    $this->log()->info('Added SSH key from file {file}.', compact('file'));
  }

}

