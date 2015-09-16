<?php
/**
 * @file
 * Contains Terminus\Outputters\StreamWriter
 */


namespace Terminus\Outputters;


/**
 * Class StreamWriter
 * @package Terminus\Outputters
 */
class StreamWriter implements OutputWriterInterface {

  /**
   * @var string
   *  The stream URI.
   */
  var $uri;

  /**
   * @param string $uri
   */
  public function __construct($uri = 'php://stdout') {
    $this->uri = $uri;
  }

  /**
   * @param string $output
   *  The formatted output to be written.
   * @return mixed
   */
  public function write($output) {
    file_put_contents($this->uri, $output);
  }
}
