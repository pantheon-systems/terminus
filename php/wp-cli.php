<?php

// Can be used by plugins/themes to check if WP-CLI is running or not
define( 'WP_CLI', true );
define( 'WP_CLI_VERSION', '0.15-alpha' );
define( 'TERMINUS_HOST', 'onebox.getpantheon.com' );
define( 'TERMINUS_PORT', '443' );
date_default_timezone_set('UTC');

include WP_CLI_ROOT . '/php/utils.php';
include WP_CLI_ROOT . '/php/login.php';
include WP_CLI_ROOT . '/php/FileCache.php';
include WP_CLI_ROOT . '/php/dispatcher.php';
include WP_CLI_ROOT . '/php/class-wp-cli.php';
include WP_CLI_ROOT . '/php/class-wp-cli-command.php';

\WP_CLI\Utils\load_dependencies();

WP_CLI::get_runner()->before_wp_load();

