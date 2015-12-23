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
   * @param string $command The command to check for availability
   * @return void
   */
  protected function checkCommand($command) {
    $command_array = explode(' ', $command);
    foreach ($command_array as $element) {
      if (strpos($element, '--') === 0) {
        continue;
      }
      if (isset($this->unavailable_commands[$element])) {
        $error_message = "$element is not available via Terminus. "
          . 'Please run it via ' . $this->client;
        if (!empty($this->unavailable_commands[$element])) {
          $error_message .= ', or you can use `terminus '
            . $this->unavailable_commands[$element]
            . '` to complete the same task';
        }
        $error_message .= '.';
        $this->failure($error_message);
      }
    }
  }

  /**
   * Verifies that there is only one argument given and no extaneous params
   *
   * @param array $args       Command(s) given in the command line
   * @param array $assoc_args Arguments and flags passed into the former
   * @return bool True if correct
   */
  protected function ensureQuotation($args, $assoc_args) {
    unset($assoc_args['site']);
    unset($assoc_args['env']);
    if (!empty($assoc_args) || (count($args) !== 1)) {
      $message  = 'Your full {client} command and its arguments ';
      $message .= 'must be in quotation marks.';
      $this->failure($message, array('client' => $this->client));
    }
    return true;
  }

  /**
   * Formats command output into an array
   *
   * @param string $string Output string to format
   * @return array
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
   * @param array $site_info Elements as follows:
   *        [string] site        Site UUID
   *        [string] environment Environment name
   * @return array Connection info
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
   * @param array $options Elements as follows:
   *        [string] server      Server to connect to
   *        [string] remote_exec Program to execute on server
   *        [array]  command     Command and arguments
   * @return array
   */
  protected function sendCommand(array $options = array()) {
    $server      = $options['server'];

    $is_normal = (Terminus::getConfig('format') == 'normal');
    $cmd       = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p '
      . $server['port'] . ' -o "AddressFamily inet"' . " "
      . escapeshellarg(
        $options['remote_exec'] . ' ' . $options['command'] . ' '
      );
    $this->log()->debug(
      'Command "{command}" is being run.',
      array('command' => escapeshellarg($cmd))
    );
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
