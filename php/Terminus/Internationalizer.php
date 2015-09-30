<?php

namespace Terminus;

use Philasearch\I18n\I18n as Lang;

class Internationalizer {

  private $internationalizer;
  private $language;

  /**
   * Sets up object
   *
   * @return [Internationalizer] $this
   */
  public function __construct() {
    $this->setInternationalizer();
    $this->setLanguage();
  }

  /**
   * Returns text in target language
   *
   * @param [string] $key          Name of key to retrieve
   * @param [array]  $replacements List of variables to replace
   * @return [string] $output
   */
  public function get($key, $replacements = array()) {
    $string_name = $this->getRoute() . '.' . $key;
    $output = $this->internationalizer->get(
      $this->language,
      $string_name,
      $replacements
    );
    return $output;
  }

  /**
   * Parses the route to find translated text
   *
   * @return [string] $route
   */
  private function getRoute() {
    $backtrace = debug_backtrace();
    $backtrace = array_reverse($backtrace);
    $route     = '';
    while ($route == '') {
      $last_command = array_shift($backtrace);
      if (strpos($last_command['class'], '_Command') != false) {
        $route = strtolower(str_replace('_Command', '', $last_command['class']));
        $route .= '.' . $last_command['function'];
      }
    }
    return $route;
  }

  /**
   * Instantiates the internationalizer
   *
   * @return [void]
   */
  private function setInternationalizer() {
    $this->internationalizer = new Lang(TERMINUS_ROOT . '/i18n');
  }

  /**
   * Sets the language to use
   *
   * @return [void]
   */
  private function setLanguage() {
    if (!isset($_SERVER['TERMINUS_LANG'])) {
      $this->language = 'en';
    } else {
      $this->language = $_SERVER['TERMINUS_LANG'];
    }
  }

}
