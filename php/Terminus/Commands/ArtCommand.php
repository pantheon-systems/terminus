<?php

namespace Terminus\Commands;

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
  public function __invoke($args, $assoc_args) {
    $artwork = $this->works[array_rand($this->works)];
    if (count($args) > 0) {
      $artwork = array_shift($args);
    }

    try {
      $artwork_content = $this->helpers->file->loadAsset("$artwork.txt");
      $this->output()->line(
        $this->colorize("%g" . base64_decode($artwork_content) . "%n")
      );
    } catch (TerminusException $e) {
      $this->failure(
        'There is no source for the requested "{artwork}" artwork.',
        compact('artwork')
      );
    }
  }

  /**
   * Returns a colorized string
   *
   * @param string $string Message to colorize for output
   * @return string
   */
  private function colorize($string) {
    $colorization_setting = $this->runner->getConfig('colorize');
    $colorize             = (
      (($colorization_setting == 'auto') && !\cli\Shell::isPiped())
      || (is_bool($colorization_setting) && $colorization_setting)
    );
    $colorized_string     = \cli\Colors::colorize($string, $colorize);
    return $colorized_string;
  }

}
