<?php

namespace Terminus\Commands;

use Terminus\Session;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\User;

/**
 * Show information for your Pantheon machine tokens
 *
 * @command machine-tokens
 */
class MachineTokensCommand extends TerminusCommand {

  /**
   * Instantiates object, ensures login
   *
   * @param array $options Options to construct the command object
   * @return MachineTokensCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
  }

  /**
   * Show a list of your macnine tokens on Pantheon
   *
   * @subcommand list
   * @alias show
   */
  public function index($args, $assoc_args) {
    $user        = Session::getUser();

    $machine_tokens = $user->machine_tokens->all();
    $data        = array();
    foreach ($machine_tokens as $id => $machine_token) {
      $data[] = array(
        'id'          => $machine_token->get('id'),
        'device_name' => $machine_token->get('device_name'),
      );
    }

    if (count($data) == 0) {
      $this->log()->warning('You have no machine tokens.');
    }

    $this->output()->outputRecordList(
      $data,
      array(
        'id'          => 'ID',
        'device_name' => 'Device Name',
      )
    );
  }

  /**
   * Delete a machine token from your account
   *
   * ## OPTIONS
   * [--machine-token-id=<id>]
   * : UUID or name of the site you want to delete
   *
   * [--force]
   * : to skip the confirmations
   */
  public function delete($args, $assoc_args) {
    $user        = Session::getUser();

    $id = $assoc_args['machine-token-id'];
    if (empty($id)) {
      $this->failure(
        'You must specify a machine token id to delete.'
      );
    }

    // Find the token
    $machine_token = $user->machine_tokens->get($assoc_args['machine-token-id']);
    if (empty($machine_token)) {
      $this->failure(
        'There are no machine tokens with the id {id}.',
        array('id' => $id)
      );
    }
    $name = $machine_token->get('device_name');

    $this->input()->confirm(
      [
        'message' => 'Are you sure you want to delete %s?',
        'context' => $name,
        'args'    => $assoc_args,
      ]
    );
    $this->log()->info(
      'Deleting {name} ...',
      array('name' => $name)
    );
    $response = $machine_token->delete();
    if ($response['status_code'] == 200) {
      $this->log()->info(
        'Deleted {name}!',
        array('name' => $name)
      );
    } else {
      $this->failure(
        'There was an problem deleting the machine token.'
      );
    }
  }

}

