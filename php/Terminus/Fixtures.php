<?php
namespace Terminus;

class Fixtures {
  static $fixtures_dir = 'tests/fixtures';
  static $current_fixture;

  /**
   * This creates a "phony" data blob that we can use for unit testing.
   */
  static function put( $fixture, $data ) {
    $key = Fixtures::getArgsKey();
    $cli_root = dirname(dirname(__DIR__));
    if( !file_exists(self::$fixtures_dir."/$key") ) {
      mkdir( sprintf( "%s/%s/%s", $cli_root, self::$fixtures_dir, $key) );
    }

    $fixture =  sprintf( "%s/%s/%s/%s", $cli_root, self::$fixtures_dir, $key, $fixture );

    // if there's already a fixture, assume we want to overwrite it
    if (file_exists($fixture)) {
      @unlink($fixture);
    }

    file_put_contents($fixture, serialize($data), LOCK_EX);
  }


  static function get( $fixture )
  {
      $key = self::$current_fixture ?: Fixtures::getArgsKey();
      $cli_root = dirname(dirname(__DIR__));

      $filename = sprintf('%s/%s/%s/%s', $cli_root, self::$fixtures_dir, $key, $fixture);
      if( file_exists($filename) ) {
        return unserialize(file_get_contents("$filename"));
      } else {
        var_dump($filename);
      }
      return false;
  }

  static function getArgsKey()
  {
    // save the cli args for later just in case we're debugging
    $args = $GLOBALS['argv'];
    array_shift($args);
    return join("-",$args);
  }

  static function setFixture($fixture) {
    self::$current_fixture = $fixture;
  }

  static function clear() {
    self::$current_fixture = false;
  }

}
