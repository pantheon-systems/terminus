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
      echo $cli_root;
    } else {
      $cli_root = constant('CLI_ROOT');
    }

    $fixture =  sprintf( "%s/%s/%s", $cli_root, self::$fixtures_dir, $key );
    file_put_contents($fixture, serialize($data), LOCK_EX);
  }


  static function get($args)
  {
      $key = self::$current_fixture ?: Fixtures::getArgsKey($args);
      echo __LINE__.":".$key.PHP_EOL;
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
    $data = $args[2];
    if (isset($data['cookies']) AND !empty($data['cookies'])) {
      $data['cookies'] = '';
    }
    $args[2] = $data;
    return md5(serialize($args));
  }

  static function setFixture($fixture) {
    self::$current_fixture = $fixture;
  }

  static function clear() {
    self::$current_fixture = false;
  }

}
