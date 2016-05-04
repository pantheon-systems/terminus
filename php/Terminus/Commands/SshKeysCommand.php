<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Configurator;
use Terminus\Session;

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
        'fingerprint' => $ssh_key->get('id'),
        'comment'     => $ssh_key->getComment(),
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
        'dir'     => Configurator::getHomeDir() . '/.ssh',
        'message' => 'Please select your public SSH key file.',
        'regex'   => '~(.*.pub)~',
      ]
    );
    $this->user->ssh_keys->addKey($file);
    $this->log()->info('Added SSH key from file {file}.', compact('file'));
  }

  /**
   * Remove an SSH key from your account
   *
   * [--fingerprint=<fingerprint>]
   * : The fingerprint of the SSH key to remove
   *
   * [--all]
   * : Use to remove all SSH keys from your account
   */
  public function delete($args, $assoc_args) {
    if (isset($assoc_args['fingerprint'])) {
      $fingerprint = $assoc_args['fingerprint'];
    } elseif (!isset($assoc_args['all'])) {
      $ssh_keys         = $this->user->ssh_keys->all();
      $display_choices  = [];
      $choices          = [];
      foreach ($ssh_keys as $id => $ssh_key) {
        $display_choices[] = $ssh_key->get('id') . ' - ' . $ssh_key->getComment();
        $choices[]         = $ssh_key->get('id');
      }
      $fingerprint = $choices[$this->input()->menu(
        [
          'autoselect_solo' => false,
          'choices'         => $display_choices,
          'message'         => 'Select a SSH key to delete',
        ]
      )];
    }

    if (isset($fingerprint)) {
      $ssh_key = $this->user->ssh_keys->get($fingerprint);
      $ssh_key->delete();
      $this->log()->info(
        'Deleted SSH key {fingerprint}.',
        compact('fingerprint')
      );
    } else {
      $this->input()->confirm(
        ['message' => 'Are you sure you want to delete ALL of your SSH keys?',]
      );
      $this->user->ssh_keys->deleteAll();
      $this->log()->info('Deleted all SSH keys.');
    }
  }

}

