<?php

namespace Terminus\Commands;

/**
 * Show information for your Pantheon instruments
 *
 * @command instruments
 */
class InstrumentsCommand extends TerminusCommand {

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
    $instruments = array_map(
      function ($instrument) {
        $info = (object)[
          'label' => $instrument->get('label'),
          'id'    => $instrument->id,
        ];
        return $info;
      },
      $this->sites->user->instruments->fetch()->all()
    );
    $this->output()->outputRecordList(
      $instruments,
      ['label' => 'Card', 'id' => 'ID',]
    );
  }

}

