<?php

namespace Terminus;

/**
 * Base class for WP-CLI commands that deal with sending SSH commands
 *
 * @package terminus
 */
abstract class CommandWithSSH extends \Terminus_Command {

  protected function send_command($server, $remote_exec, $args, $assoc_args) {
    # unset CLI args
    unset($assoc_args['site']);

    $remote_cmd = $remote_exec . ' ';

    $remote_cmd .= implode(' ', $args);

    foreach ($assoc_args as $key => $value) {
      if ($value != 1) {
        $remote_cmd .= ' --' . $key . '=' . $value;
      }
      else {
        $remote_cmd .= ' --' . $key;
      }
    }

    $cmd = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p ' . $server['port'] . ' -o "AddressFamily inet"' . " '" . $remote_cmd . "'";

    passthru( $cmd, $exit_code );
    exit( $exit_code );
  }
}
