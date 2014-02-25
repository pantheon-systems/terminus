<?php

use \Terminus\Dispatcher,
  \Terminus\Utils,
  \Terminus\CommandWithSSH;


class Drush_Command extends CommandWithSSH {

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush commands you intend to run.
   *
   * [--<flag>=<value>]
   * : Additional Drush flag(s) to pass in to the command.
   *
   * --site=<site>
   * : The name (DNS shortname) of your site on Pantheon.
   *
   * [--env=<environment>]
   * : Your Pantheon environment. Default: dev
   *
   */
  function __invoke( $args, $assoc_args ) {
    $site_name = $assoc_args['site'];
    if (isset($assoc_args['environment'])) {
      $environment = $assoc_args['environment'];
    }
    else {
      $environment = 'dev';
    }
    $site = $this->fetch_site($site_name);
    if (!$site) {
      Terminus::error("Command could not be completed.");
      exit;
    }
    # TODO: validate environment quickly.
    #if (!isset($site->environments->$environment)) {
    #  Terminus::error("The '$environment' environment does not exist.");
    #}

    # see https://github.com/pantheon-systems/titan-mt/blob/master/dashboardng/app/workshops/site/models/environment.coffee
    $server = Array(
      'user' => "$environment.$site->site_uuid",
      'host' => "appserver.$environment.$site->site_uuid.drush.in",
      'port' => '2222'
    );

    # Sanitize assoc args.
    unset($assoc_args['site']);
    if (isset($assoc_args['environment'])) {
      unset($assoc_args['environment']);
    }
    # Create user-friendly output
    $command = implode( $args, ' ' );
    $flags = '';
    foreach ( $assoc_args as $k => $v ) {
      if (isset($v) && (string) $v != '') {
        $flags .= "--$k=$v";
      }
      else {
        $flags .= "--$k";
      }
    }
    Terminus::line( "Running drush $command $flags against $site_name-$environment" );
    $this->send_command($server, 'drush', $args, $assoc_args );
  }

}

Terminus::add_command( 'drush', 'Drush_Command' );
