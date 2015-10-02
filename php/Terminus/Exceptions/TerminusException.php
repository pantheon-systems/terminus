<?php
/**
 * @file
 * Contains Terminus\Exceptions\TerminusException
 */


namespace Terminus\Exceptions;

use Terminus\Internationalizer as I18n;

/**
 * Class TerminusException
 * @package Terminus\Exceptions
 */
class TerminusException extends \Exception {
  /**
   * @var array
   */
  private $replacements;

  public function __construct($message = null, $replacements = array(), $code = 0) {
    $i18n    = new I18n();
    //$message = $i18n->get($message, $replacements);
    parent::__construct($message, $code);
  }
}
