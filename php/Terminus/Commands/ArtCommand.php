<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Utils;
use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;

/**
 * Print the Pantheon art
 *
 * @command art
 */
class ArtCommand extends TerminusCommand {

  private $works = array('druplicon', 'fist', 'unicorn', 'wordpress');

  /**
   * View Pantheon ASCII artwork
   *
   * ## OPTIONS
   * <druplicon|fist|unicorn|wordpress>
   */
  function __invoke($args, $assoc_args) {
    $artwork = $this->works[array_rand($this->works)];
    if (count($args) > 0) {
      $artwork = array_shift($args);
    }

    try {
      $artwork_content = Utils\loadAsset("$artwork.txt");
      echo Utils\colorize(
        "%g" . base64_decode($artwork_content) . "%n"
      ) . PHP_EOL;
    } catch (TerminusException $e) {
      $this->failure(
        'There is no source for the requested "{artwork}" artwork.',
        compact('artwork')
      );
    }
  }

}
