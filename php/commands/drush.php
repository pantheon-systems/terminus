<?php

use \Terminus\Dispatcher,
  \Terminus\Utils,
  \Terminus\CommandWithSSH,
  \Terminus\SiteFactory,
  \Terminus\Helpers\Input;


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
   * [--site=<site>]
   * : The name (DNS shortname) of your site on Pantheon.
   *
   * [--env=<environment>]
   * : Your Pantheon environment. Default: dev
   *
   */
  function __invoke( $args, $assoc_args ) {
    if (isset($assoc_args['env'])) {
      $environment = $assoc_args['env'];
    }
    else {
      $environment = 'dev';
    }
    $site = SiteFactory::instance(Input::sitename($assoc_args));
    if (!$site) {
      Terminus::error("Command could not be completed. Unknown site specified.");
      exit;
    }

    $server = Array(
      'user' => "$environment.{$site->getId()}",
      'host' => "appserver.$environment.{$site->getId()}.drush.in",
      'port' => '2222'
    );

    if (strpos(TERMINUS_HOST, 'onebox') !== FALSE) {
      $server['user'] = "appserver.$environment.{$site->getId()}";
      $server['host'] = TERMINUS_HOST;
    }

    # Sanitize assoc args so we don't try to pass our own flags.
    # TODO: DRY this out?
    unset($assoc_args['site']);
    if (isset($assoc_args['env'])) {
      unset($assoc_args['env']);
    }

    # Create user-friendly output
    $command = implode( $args, ' ' );
    $flags = '';
    foreach ( $assoc_args as $k => $v ) {
      if (isset($v) && (string) $v != '') {
        $flags .= "--$k=$v ";
      }
      else {
        $flags .= "--$k ";
      }
    }
    Terminus::line( "Running drush %s %s on %s-%s", array($command, $flags, $site->getName(), $environment));
    $this->send_command($server, 'drush', $args, $assoc_args );
  }

}

Terminus::add_command( 'drush', 'Drush_Command' );
