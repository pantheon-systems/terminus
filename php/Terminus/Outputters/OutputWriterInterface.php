<?php

/**
 * @file
 */

namespace Terminus\Outputters;

/**
 * Interface OutputWriterInterface
 * @package Terminus\Outputters
 *
 * Describes an object which can send the return value of a command somewhere.
 */
interface OutputWriterInterface {

  /**
   * @param string $output The formatted output to be written.
   * @return void
   */
  public function write($output);

}
