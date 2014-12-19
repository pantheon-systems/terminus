<?php
namespace Terminus;

use Terminus\Session;
use Terminus\FileCache;

class EnvironmentFactory {
  /**
   * Instatiate an environment object
   * @param $site (object) required
   * @param $name (string) required
   * @param $options (array) optional -- no options currently available
   */
  public static function load($site, $name, $options=array()) {
    $class_name = '\Terminus\Environment'.ucfirst($name);
    if (!class_exists($class_name)) {
      $class_name = '\Terminus\Environment';
    }
    if (isset($options['hydrate_with'])) {
      $environment = $options['hydrate_with'];
      $environment->name = $name;
    }

    $object = new $class_name($site, $environment);
    return $object;
  }

}
