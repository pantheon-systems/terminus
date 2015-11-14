<?php

/**
 * @file Contains Terminus\Exceptions\TerminusException
 */

namespace Terminus\Exceptions;

/**
 * Class TerminusException
 * @package Terminus\Exceptions
 */
class TerminusException extends \Exception {
  /**
   * @var [array]
   */
  private $replacements;

  /**
   * Object constructor. Sets context array as replacements property
   *
   * @param [string]  $message      Message to send when throwing the exception
   * @param [array]   $replacements Context array to interpolate into message
   * @param [integer] $code         Exit code
   * @return [TerminusException] $this
   */
  public function __construct(
    $message = null,
    $replacements = array(),
    $code = 0
  ) {
    $this->replacements = $replacements;
    parent::__construct($message, $code);
  }

  /**
   * Returns the replacements context array
   *
   * @return [array] $this->replacements
   */
  public function getReplacements() {
    return $this->replacements;
  }

}
