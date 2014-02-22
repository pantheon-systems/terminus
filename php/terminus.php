<?php
// Can be used by plugins/themes to check if Terminus is running or not
define( 'Terminus', true );
<<<<<<< HEAD
define( 'TERMINUS_VERSION', '0.0.0-alpha' );
=======
define( 'TERMINUS_VERSION', '0.15-alpha' );
>>>>>>> 49f2c845eb89cb2e8f0c34652a444e660df57210
date_default_timezone_set('UTC');

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/login.php';
include TERMINUS_ROOT . '/php/FileCache.php';
include TERMINUS_ROOT . '/php/dispatcher.php';
include TERMINUS_ROOT . '/php/class-terminus.php';
include TERMINUS_ROOT . '/php/class-terminus-command.php';

\Terminus\Utils\load_dependencies();

if (isset($_SERVER['TERMINUS_HOST']) && $_SERVER['TERMINUS_HOST'] != '') {
  define( 'TERMINUS_HOST', $_SERVER['TERMINUS_HOST'] );
  \cli\line(\cli\Colors::colorize('%YNote: using custom target "'. $_SERVER['TERMINUS_HOST'] .'"%n'));
}
else {
  define( 'TERMINUS_HOST', 'terminus.getpantheon.com' );
}
define( 'TERMINUS_PORT', '443' );


Terminus::get_runner()->run();

