<?php

namespace Terminus\Helpers;

use Terminus\Commands\TerminusCommand;

abstract class TerminusHelper {

  /**
   * @var TerminusCommand
   */
  protected $command;

  /**
   * TerminusHelper constructor.
   *
   * @param array $options Options and dependencies for this helper
   * @return TerminusHelper $this
   */
  public function __construct(array $options = []) {
    $this->command = $options['command'];
  }

}