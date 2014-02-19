<?php

// Can be used by plugins/themes to check if WP-CLI is running or not
define( 'WP_CLI', true );
define( 'TERMINUS_VERSION', '0.15-alpha' );
define( 'TERMINUS_HOST', 'onebox.getpantheon.com' );
define( 'TERMINUS_PORT', '443' );
date_default_timezone_set('UTC');

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/login.php';
include TERMINUS_ROOT . '/php/FileCache.php';
include TERMINUS_ROOT . '/php/dispatcher.php';
include TERMINUS_ROOT . '/php/class-terminus.php';
include TERMINUS_ROOT . '/php/class-terminus-command.php';

\WP_CLI\Utils\load_dependencies();

WP_CLI::get_runner()->before_wp_load();

