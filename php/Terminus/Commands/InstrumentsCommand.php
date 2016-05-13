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
    die(print_r($this->sites, true));
    $data = $this->sites->user->instruments->fetch()->list('label', 'id');
    $this->output()->outputRecordList($data);
  }

}

