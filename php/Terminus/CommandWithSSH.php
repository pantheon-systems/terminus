<?php

namespace Terminus;

use Terminus;
use TerminusCommand;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 */
abstract class CommandWithSSH extends TerminusCommand {

  /**
   * Formats command output into an array
   *
   * @param [string] $string Output string to format
   * @return [array] $formatted_data
   */
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

  /**
   * Sends command through SSH
   *
   * @param [string] $server      Server to connect to
   * @param [string] $remote_exec Command to execute on server
   * @param [array]  $args        Args from command line
   * @param [array]  $assoc_args  Arguments sent to command
   * @return [array] $formatted_result
   */
  protected function sendCommand($server, $remote_exec, $args, $assoc_args) {
    //Unset CLI args
    unset($assoc_args['site']);

    $remote_cmd = $remote_exec . ' ';

    foreach ($args as $arg) {
      $remote_cmd .= escapeshellarg($arg) . ' ';
    }

    foreach ($assoc_args as $key => $value) {
      $remote_cmd .= ' --' . $key;
      if ($value != 1) {
        $remote_cmd .= '=' . escapeshellarg($value);
      }
    }

    $is_normal = (Terminus::getConfig('format') == 'normal');
    $cmd       = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p '
      . $server['port'] . ' -o "AddressFamily inet"' . " "
      . escapeshellarg($remote_cmd);
    if (!$is_normal) {
      ob_start();
    }
    passthru($cmd, $exit_code);
    if (!$is_normal) {
      $result = ob_get_clean();
    }
    if (Terminus::getConfig('format') == 'silent') {
      $this->logger->info($result);
    }

    if ($exit_code == 255) {
      $this->failure(
        'Failed to connect. Check your credentials and the target environment.',
        array(),
        $exit_code
      );
    }
    if (!isset($result)) {
      return true;
    }
    $formatted_result = $this->formatOutput($result);
    return $formatted_result;
  }

}
