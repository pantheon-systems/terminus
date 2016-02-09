<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Models\Collections\Sites;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 */
abstract class CommandWithSSH extends TerminusCommand {
  /**
   * @var string Name of the client to run a command on the platform
   */
  protected $client = '';

  /**
   * @var string Name of the command to be run as it will be used on server
   */
  protected $command = '';

  /**
   * @var string[] A hash of commands which do not work in Terminus. The key
   *   is the Drush command, and the value is the Terminus equivalent, and
   *   blank if DNE.
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
   * Checks the site's mode and suggests SFTP if it is not set.
   *
   * @param Environment $environment Environment object to check mode of
   * @return void
   */
  protected function checkConnectionMode($environment) {
    if ($environment->getConnectionMode() != 'sftp') {
      $message  = 'Note: This environment is in read-only Git mode. If you ';
      $message .= 'want to make changes to the codebase of this site ';
      $message .= '(e.g. updating modules or plugins), you will need to ';
      $message .= 'toggle into read/write SFTP mode first.';
      $this->log()->warning($message);
    }
  }

  /**
   * Verifies that there is only one argument given and no extaneous params
   *
   * @param string[] $args       Command(s) given in the command line
   * @param string[] $assoc_args Arguments and flags passed into the former
   * @return bool True if correct
   */
  protected function ensureQuotation($args, $assoc_args) {
    unset($assoc_args['site']);
    unset($assoc_args['env']);
    if (!empty($assoc_args) || (count($args) !== 1)) {
      $message  = 'Your {client} subcommands and arguments must be in ';
      $message .= "quotation marks.\n    Example: terminus {command} ";
      $message .= '"subcommand --arg=value" --site=<site> --env=<env>';

      $this->failure(
        $message,
        ['client' => $this->client, 'command' => $this->command]
      );
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
   * Parent function to SSH-based command invocations
   *
   * @param string[] $args       Command(s) given in the command line
   * @param string[] $assoc_args Arguments and flags passed into the former
   * @return array Elements as follow:
   *         Site   site    Site being invoked
   *         string env_id  Name of the environment being invoked
   *         string command Command to run remotely
   *         string server  Server connection info
   */
  protected function getElements($args, $assoc_args) {
    $this->ensureQuotation($args, $assoc_args);
    $command = array_pop($args);
    $this->checkCommand($command);

    $sites = new Sites();
    $site  = $sites->get($this->input()->siteName(array('args' => $assoc_args)));
    if (!$site) {
      $this->failure('Command could not be completed. Unknown site specified.');
    }

    $env_id = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    if (!in_array($env_id, ['test', 'live'])) {
      $this->checkConnectionMode($site->environments->get($env_id));
    }

    $elements = array(
      'site'    => $site,
      'env_id'  => $env_id,
      'command' => $command,
      'server'  => $this->getAppserverInfo(
        array('site' => $site->get('id'), 'environment' => $env_id)
      )
    );
    return $elements;
  }

  /**
   * Sends command through SSH
   *
   * @param array $options Elements as follows:
   *        Site   site    Site being invoked
   *        string env_id  Name of the environment being invoked
   *        string command Command to run remotely
   *        string server  Server connection info
   * @return array
   */
  protected function sendCommand(array $options = array()) {
    $this->log()->info(
      sprintf('Running %s {cmd} on {site}-{env}', $this->command),
      array(
        'cmd'   => $options['command'],
        'site'  => $options['site']->get('name'),
        'env'   => $options['env_id'],
      )
    );
    $server    = $options['server'];
    $is_normal = ($this->log()->getOptions('logFormat') == 'normal');
    $cmd       = 'ssh -T ' . $server['user'] . '@' . $server['host'] . ' -p '
      . $server['port'] . ' -o "AddressFamily inet"' . " "
      . escapeshellarg(
        $this->command . ' ' . $options['command'] . ' '
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
    if ($this->log()->getOptions('logFormat') == 'silent') {
      $this->log()->info($result);
    }

    if ((boolean)$exit_code) {
      $this->failure(
        'Either we could not connect, or {client} has exited with code {code}',
        ['client' => $this->client, 'code' => $exit_code],
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
