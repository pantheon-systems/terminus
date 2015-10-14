<?php

use Terminus\Dispatcher;
use Terminus\Exceptions\TerminusException;
use Terminus\Utils;
use Terminus\CommandWithSSH;
use Terminus\Models\Collections\Sites;
use Terminus\Helpers\Input;


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
    $environment = Input::env($assoc_args);
    $sites = new Sites();
    $site = $sites->get(Input::sitename($assoc_args));
    if (!$site) {
      throw new TerminusException("Command could not be completed. Unknown site specified.");
    }

    $server = Array(
      'user' => "$environment.{$site->get('id')}",
      'host' => "appserver.$environment.{$site->get('id')}.drush.in",
      'port' => '2222'
    );

    if (strpos(TERMINUS_HOST, 'onebox') !== FALSE) {
      $server['user'] = "appserver.$environment.{$site->get('id')}";
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
    if (in_array(\Terminus::getConfig('format'), array('bash', 'json', 'silent'))) {
      $assoc_args['pipe'] = 1;
    }
    $this->log()->info(
      "Running drush {cmd} {flags} on {site}-{env}",
      array(
        'cmd' => $command,
        'flags' => $flags,
        'site' => $site->get('name'),
        'env' => $environment
      )
    );
    $result = $this->send_command($server, 'drush', $args, $assoc_args);
    if (Terminus::getConfig('format') != 'normal') {
      $this->output()->outputRecordList($result);
    }
  }

}

Terminus::addCommand( 'drush', 'Drush_Command' );
