<?php

include TERMINUS_ROOT . '/php/FileCache.php';

use \Terminus\Utils;
use \Terminus\FileCache;

class Terminus {

  public static function get_cache() {
    static $cache;

    if ( !$cache ) {
      $home = getenv( 'HOME' );
      if ( !$home ) {
        // sometime in windows $HOME is not defined
        $home = getenv( 'HOMEDRIVE' ) . '/' . getenv( 'HOMEPATH' );
      }
      $dir = getenv( 'TERMINUS_CACHE_DIR' ) ? : "$home/.pantheon";

      // 6 months, 300mb
      $cache = new FileCache( $dir, 15552000, 314572800 );

      // clean older files on shutdown with 1/50 probability
      if ( 0 === mt_rand( 0, 50 ) ) {
        register_shutdown_function( function () use ( $cache ) {
          $cache->clean();
        } );
      }
    }
    return $cache;
  }

}
