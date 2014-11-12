<?php
namespace Terminus;

use Terminus\Session;
use Terminus\FileCache;

class EnvironmentFactory {
  /**
   * Instatiate an environment object
   * @param $site (object) required
   * @param $environment (string) required
   * @param $options (array) optional -- no options currently available
   */
  public static function load($site, $environment, $options=array()) {
    $class_name = '\Terminus\Environment'.ucfirst($environment);
    if (class_exists($class_name)) {
      if (isset($options['hydrate_with'])) {
        $environment = $options['hydrate_with'];
      }
      $object = new $class_name($site, $environment);
      return $object;
    }
  }

}
