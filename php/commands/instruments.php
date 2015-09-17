<?php

use Terminus\Models\User;

/**
 * Show information for your Pantheon instruments
 */
class Instruments_Command extends TerminusCommand {

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

    $this->outputter->outputRecordList($data);
  }

}

Terminus::add_command('instruments', 'Instruments_Command');
