<?php

namespace Terminus;

use Terminus;
use TerminusCommand;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 *
 * @package terminus
 */
abstract class CommandWithSSH extends TerminusCommand {

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

    $is_normal = (Terminus::get_config('format') == 'normal');
    $cmd = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p ' . $server['port'] . ' -o "AddressFamily inet"' . " " . escapeshellarg($remote_cmd);
    if (!$is_normal) {
      ob_start();
    }
    passthru($cmd, $exit_code);
    if (!$is_normal) {
      $result = ob_get_clean();
    }
    if (Terminus::get_config('format') == 'silent') {
      $this->logger->info($result);
    }

    if ($exit_code == 255) {
      $this->failure('Failed to connect. Check your credentials, and that you are specifying a valid environment.');
      return $exit_code;
    }
    if (!isset($result)) {
      return true;
    }
    return $this->formatOutput($result);
  }

  protected function formatOutput($string) {
    $exploded_string = explode("\n", $string);
    $formatted_data  = array();
    foreach ($exploded_string as $key => $value) {
      if (!in_array($value, array('', null))) {
        $formatted_data[$key] = explode("\t", $value);
        if (count($formatted_data[$key] == 1)) {
          $formatted_data[$key] = $value;
        }
      }
    }
    return $formatted_data;
  }
}
