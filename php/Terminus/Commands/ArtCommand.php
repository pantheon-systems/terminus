<?php

use Terminus\Utils;
use Terminus\Exceptions\TerminusException;

/**
 * Print the Pantheon art
 */
class ArtCommand extends TerminusCommand {

  private $works = array('druplicon', 'fist', 'unicorn', 'wordpress');

  /**
   * View Pantheon ASCII artwork
   *
   * ## OPTIONS
   * <druplicon|fist|unicorn|wordpress>
   *
   * @param [array] $args       The non-param aspects of the entered command
   * @param [array] $assoc_args The param aspects of the entered command
   * @return [void]
   */
  function __invoke($args, $assoc_args) {
    $artwork = $this->works[array_rand($this->works)];
    if (count($args) > 0) {
      $artwork = array_shift($args);
    }

    try {
      $artwork_content = Utils\loadAsset("$artwork.txt");
      echo Terminus::colorize(
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

Terminus::addCommand('art', 'ArtCommand');
