<?php

namespace Terminus\Utils;

function load_dependencies() {
  if ( 0 === strpos( TERMINUS_ROOT, 'phar:' ) ) {
    require TERMINUS_ROOT . '/vendor/autoload.php';
    return;
  }

  $has_autoload = false;

  foreach ( get_vendor_paths() as $vendor_path ) {
    if ( file_exists( $vendor_path . '/autoload.php' ) ) {
      require $vendor_path . '/autoload.php';
      $has_autoload = true;
      break;
    }
  }

  if ( !$has_autoload ) {
    fputs( STDERR, "Internal error: Can't find Composer autoloader.\n" );
    exit(3);
  }
}

function get_vendor_paths() {
  return array(
    TERMINUS_ROOT . '/../../../vendor',  // part of a larger project / installed via Composer (preferred)
    TERMINUS_ROOT . '/vendor',           // top-level project / installed as Git clone
  );
}

// Using require() directly inside a class grants access to private methods to the loaded code
function load_file( $path ) {
  require $path;
}

// function load_command( $name ) {
//   $path = TERMINUS_ROOT . "/php/commands/$name.php";

//   if ( is_readable( $path ) ) {
//     include_once $path;
//   }
// }

// function load_all_commands() {
//   $cmd_dir = TERMINUS_ROOT . '/php/commands';

//   $iterator = new \DirectoryIterator( $cmd_dir );

//   foreach ( $iterator as $filename ) {
//     if ( '.php' != substr( $filename, -4 ) )
//       continue;

//     include_once "$cmd_dir/$filename";
//   }
// }

/**
 * Check if we're running in a Windows environment (cmd.exe).
 */
function is_windows() {
  return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}
