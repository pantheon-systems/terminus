<?php

use \WP_CLI\Dispatcher,
  \WP_CLI\Utils,
  \WP_CLI\CommandWithSSH;


class WP_Command extends CommandWithSSH {

  /**
   * Invoke `wp` commands on a Pantheon development site
   *
   */
  function __invoke( $args, $assoc_args ) {

    $server = Array(
      'user' => 'appserver.dev.a16e090c-2838-45b5-81f6-236c0032b801',
      'host' => '192.237.241.50',
      'port' => '2222'
    );

    $this->send_command($server, 'wp', $args, $assoc_args );
  }

}

WP_CLI::add_command( 'wp', 'WP_Command' );
