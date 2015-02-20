<?php
// Can be used by plugins/themes to check if Terminus is running or not
define( 'Terminus', true );
define( 'TERMINUS_VERSION', '1.0.0');
$source = 'unknown';
if ('cli' === PHP_SAPI && isset($argv)) {
    $source = explode('/',$argv[0]);
    $source = end($source);
}
define('TERMINUS_SCRIPT',$source);
date_default_timezone_set('UTC');
include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/login.php';
include TERMINUS_ROOT . '/php/FileCache.php';
include TERMINUS_ROOT . '/php/dispatcher.php';
include TERMINUS_ROOT . '/php/class-terminus.php';
include TERMINUS_ROOT . '/php/class-terminus-command.php';

\Terminus\Utils\load_dependencies();

# Set a custom exception handler
set_exception_handler('\Terminus\Utils\handle_exception');

if (isset($_SERVER['TERMINUS_HOST']) && $_SERVER['TERMINUS_HOST'] != '') {
  define( 'TERMINUS_HOST', $_SERVER['TERMINUS_HOST'] );
  \cli\line(\cli\Colors::colorize('%YNote: using custom target "'. $_SERVER['TERMINUS_HOST'] .'"%n'));
}
else {
  define( 'TERMINUS_HOST', 'dashboard.getpantheon.com' );
}
define( 'TERMINUS_PORT', '443' );


Terminus::get_runner()->run();
