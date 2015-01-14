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
    if (isset($args[2]['cookies']) AND !empty($args[2]['cookies'])) {
      $args[2]['cookies'] = '';
    }
    $key = md5(serialize($args));
    return $key;
  }

  static function setFixture($fixture) {
    self::$current_fixture = $fixture;
  }

  static function clear() {
    self::$current_fixture = false;
  }

}
