<?php

/**
 * @file Contains Terminus\Outputters\StreamWriter
 */

namespace Terminus\Outputters;

/**
 * Class StreamWriter
 * @package Terminus\Outputters
 */
class StreamWriter implements OutputWriterInterface {
  /**
   * @var string The stream URI
   */
  var $uri;

  /**
   * Object constructor. Assigns URI property
   *
   * @param string $uri File or stream to write to
   */
  public function __construct($uri = 'php://stdout') {
    $this->uri = $uri;
  }

  /**
   * Outputs given data to file or stream
   *
   * @param string $output The formatted output to be written.
   * @return void
   */
  public function write($output) {
    file_put_contents($this->uri, $output);
  }

}
