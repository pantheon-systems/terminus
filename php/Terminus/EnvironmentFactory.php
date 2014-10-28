<?php
namespace Terminus;

use Terminus\Session;
use Terminus\FileCache;

class EnvironmentFactory {
  /**
   * Instatiate an environment object
   * @param $environment (string) required
   */
  public static function load($site_id, $environment, $options=array()) {
    $class_name = '\Terminus\Environment'.ucfirst($environment);
    if (class_exists($class_name)) {
      $object = new $class_name($site_id);
      return $object;
    }
  }

}
