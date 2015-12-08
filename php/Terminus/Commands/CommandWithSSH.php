<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Commands\TerminusCommand;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 */
abstract class CommandWithSSH extends TerminusCommand {
  /**
   * Name of client that command will be run on server via
   */
  protected $client = '';

  /**
   * A hash of commands which do not work in Terminus
   * The key is the drush command
   * The value is the Terminus equivalent, blank if DNE
   */
  protected $unavailable_commands = array();

  /**
   * Checks to see if the command is not available in Terminus and, if not,
   * it will refer you to an equivalent Terminus command, if such exists.
   *
   * @param [string] $command The command to check for availability
   * @return [void]
   */
  protected function checkCommand($command) {
    if (isset($this->unavailable_commands[$command])) {
      $error_message = "$command is not available via Terminus. "
        . 'Please run it via ' . $this->client;
      if (!empty($this->unavailable_commands[$command])) {
        $error_message .= ', or you can use `terminus '
          . $this->unavailable_commands[$command]
          . '` to complete the same task';
      }
      $error_message .= '.';
      $this->failure($error_message);
    }
  }

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
   * Parses server information for connections
   *
   * @param [array] $site_info Elements as follows:
   *        [string] site        Site UUID
   *        [string] environment Environment name
   * @return [array] $server Connection info
   */
  protected function getAppserverInfo(array $site_info = array()) {
    $site_id = $site_info['site'];
    $env_id  = $site_info['environment'];
    $server  = array(
      'user' => "$env_id.$site_id",
      'host' => "appserver.$env_id.$site_id.drush.in",
      'port' => '2222'
    );
    if ($ssh_host = getenv('TERMINUS_SSH_HOST')) {
      $server['user'] = "appserver.$env_id.$site_id";
      $server['host'] = $ssh_host;
    } else if (strpos(TERMINUS_HOST, 'onebox') !== false) {
      $server['user'] = "appserver.$env_id.$site_id";
      $server['host'] = TERMINUS_HOST;
    }
    return $server;
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
    $this->log()->debug('Running command "{cmd}".', compact('cmd'));
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
