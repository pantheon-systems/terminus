<?php
namespace Terminus;

class Fixtures {
  static $fixtures_dir = 'tests/fixtures';
  static $current_fixture;

  /**
   * This creates a "phony" data blob that we can use for unit testing.
   */
  static function put($args, $data) {
    $key = Fixtures::getArgsKey($args);

    if (!defined('CLI_ROOT')) {
      $cli_root = dirname(dirname(__DIR__));
    } else {
      $cli_root = constant('CLI_ROOT');
    }
    $fixture =  sprintf( "%s/%s/%s", $cli_root, self::$fixtures_dir, $key );
    file_put_contents($fixture, serialize($data));
  }


  static function get($args)
  {
      $key = Fixtures::getArgsKey($args);
      $cli_root = dirname(dirname(__DIR__));
      $filename = sprintf('%s/%s/%s', $cli_root, self::$fixtures_dir, $key);
      if( file_exists($filename) ) {
        return unserialize(file_get_contents("$filename"));
      } else {
        var_dump($filename);
      }
      return false;
  }

  static function getArgsKey($args)
  {
    // strip UUIDs
    $string = preg_replace('#https://dashboard.getpantheon.com/api/(sites|users|ogranizations)\/(.+)\/(.+)$#s','$1/$3',$args[0]);
    $key = sprintf('%s%s', $args[1], strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string))));
    return $key;
  }

  static function setFixture($fixture) {
    self::$current_fixture = $fixture;
  }

  static function clear() {
    self::$current_fixture = false;
  }

}
