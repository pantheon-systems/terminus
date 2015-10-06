<?php
/**
 * @file
 * Contains Terminus\Exceptions\TerminusException
 */


namespace Terminus\Exceptions;


/**
 * Class TerminusException
 * @package Terminus\Exceptions
 */
class TerminusException extends \Exception {
  /**
   * @var array
   */
  private $replacements;

  public function __construct($message = null, $replacements = array(), $code = 0)
  {
    $this->replacements = $replacements;
    parent::__construct($message, $code);
  }

  /**
   * @return array
   */
  public function getReplacements() {
    return $this->replacements;
  }
}
