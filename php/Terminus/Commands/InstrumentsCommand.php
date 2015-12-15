<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Auth;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\User;

/**
 * Show information for your Pantheon instruments
 */
class InstrumentsCommand extends TerminusCommand {

  /**
   * Instantiates object, ensures login
   */
  public function __construct() {
    Auth::ensureLogin();
    parent::__construct();
  }

  /**
   * Show a list of your instruments on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $user        = new User();
    $instruments = $user->instruments->all();
    $data        = array();
    foreach ($instruments as $id => $instrument) {
      $data[] = array(
        'label' => $instrument->get('label'),
        'id'    => $instrument->get('id'),
      );
    }

    $this->output()->outputRecordList($data);
  }

}

Terminus::addCommand('instruments', 'InstrumentsCommand');
