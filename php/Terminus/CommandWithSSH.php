<?php

namespace Terminus;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 *
 * @package terminus
 */
abstract class CommandWithSSH extends \TerminusCommand {

  protected function send_command($server, $remote_exec, $args, $assoc_args) {
    # unset CLI args
    unset($assoc_args['site']);

    $remote_cmd = $remote_exec . ' ';

    foreach ($args as $arg) {
      $remote_cmd .= escapeshellarg($arg) . ' ';
    }

    foreach ($assoc_args as $key => $value) {
      if ($value != 1) {
        $remote_cmd .= ' --' . $key . '=' . escapeshellarg($value);
      }
      else {
        $remote_cmd .= ' --' . $key;
      }
    }

    $cmd = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p ' . $server['port'] . ' -o "AddressFamily inet"' . " " . escapeshellarg($remote_cmd);

    passthru( $cmd, $exit_code );
    if ($exit_code == 255) {
      \Terminus::error("Failed to connect. Check your credentials, and that you are specifying a valid environment.");
    }
    return( $exit_code );
  }
}
