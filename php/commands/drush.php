<?php

use \WP_CLI\Dispatcher,
  \WP_CLI\Utils;


class Drush_Command extends WP_CLI_Command {

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   */
  function __invoke( $args, $assoc_args ) {
    print_r( $args );
    print_r( $assoc_args );
  }

}

WP_CLI::add_command( 'drush', 'Drush_Command' );

