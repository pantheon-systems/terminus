<?php
namespace Terminus;

class Fixtures {
  static $fixtures_dir = 'fixtures';

  /**
   * This creates a "phony" data blob that we can use for unit testing.
   */
  static function put( $fixture, $data ) {
    $key = Fixtures::getArgsKey();
    $cli_root = dirname(dirname(__DIR__));
    if( !file_exists("$cli_root/fixtures/$key") ) {
      mkdir( sprintf( "%s/%s/%s", $cli_root, self::$fixtures_dir, $key) );
    }

    $fixture =  sprintf( "%s/%s/%s/%s", $cli_root, self::$fixtures_dir, $key, $fixture );

    // if there's already a fixture, assume we want to overwrite it
    if (file_exists($fixture)) {
      unlink($fixture);
    }

    file_put_contents($fixture, $data, LOCK_EX);
  }


  static function get( $fixture )
  {
      $key = Fixtures::getArgsKey();
      $cli_root = dirname(dirname(__DIR__));
      $filename = sprintf('%s/%s/%s/%s', $cli_root, self::$fixtures_dir, $key, $fixture);
      if( file_exists($filename) ) {
        return file_get_contents( $filename );
      }
      return false;
  }

  static function getArgsKey()
  {
    // save the cli args for later just in case we're debugging
    $args = $GLOBALS['argv'];
    array_shift($args);
    return join(":",$args);
  }

}
