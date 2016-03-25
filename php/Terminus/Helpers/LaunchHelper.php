<?php

namespace Terminus\Helpers;

use Terminus\Helpers\TerminusHelper;
use Terminus\Utils;

class LaunchHelper extends TerminusHelper {

  /**
   * Composes associative arguments into a command string
   *
   * @param array $assoc_args Arguments for command line in array form
   * @return string Command string form of param
   */
  public function assocArgsToStr($assoc_args) {
    $return = '';
    foreach ($assoc_args as $key => $value) {
      if ($value === true) {
        $return .= " --$key";
      } else {
        $return .= " --$key=" . escapeshellarg($value);
      }
    }
    return $return;
  }

  /**
   * Launch an external process that takes over I/O.
   *
   * @param array $arg_options Elements as follow:
   *        string command         Command to call
   *        array  descriptor_spec How PHP passes descriptor to child process
   *        bool   exit_on_error   True to exit if the command returns error
   * @return int   The command exit status
   */
  public function launch(array $arg_options = []) {
    $default_options = [
      'exit_on_error'   => true,
      'descriptor_spec' => [STDIN, STDOUT, STDERR],
    ];
    $options         = array_merge($default_options, $arg_options);
    $command         = $options['command'];
    if (Utils\isWindows()) {
      $command = '"' . $command . '"';
    }
    $status = proc_close(
      proc_open($command, $options['descriptor_spec'], $pipes)
    );

    if ((boolean)$status && $options['exit_on_error']) {
      exit($status);
    }

    return $status;
  }

  /**
   * Launch another Terminus command using the runtime arguments for the
   * current process
   *
   * @param array $arg_options Elements as follow:
   *        string command       Command to call
   *        array  args          Positional arguments to use
   *        array  assoc_args    Associative arguments to use
   *        bool   exit_on_error True to exit if the command returns error
   * @return int   The command exit status
   */
  public function launchSelf(array $arg_options = []) {
    $default_options = [
      'args'          => [],
      'assoc_args'    => [],
      'exit_on_error' => true
    ];
    $options         = array_merge($default_options, $arg_options);

    if (isset($GLOBALS['argv'])) {
      $script_path = $GLOBALS['argv'][0];
    } else {
      $script_path = __DIR__ . '/../../boot-fs.php';
    }

    $escaped_args = array_map('escapeshellarg', $options['args']);
    $full_command = sprintf(
      '"%s" "%s" %s %s %s',
      $this->getPhpBinary(),
      $script_path,
      $options['command'],
      implode(' ', $escaped_args),
      $this->assocArgsToStr($options['assoc_args'])
    );
    $status       = $this->launch(
      [
        'command'       => $full_command,
        'exit_on_error' => $options['exit_on_error']
      ]
    );
    return $status;
  }

  /**
   * Returns location of PHP with which to run Terminus
   *
   * @return string
   */
  private function getPhpBinary() {
    if (getenv('TERMINUS_PHP_USED')) {
      $php_bin = getenv('TERMINUS_PHP_USED');
    } elseif (getenv('TERMINUS_PHP')) {
      $php_bin = getenv('TERMINUS_PHP');
    } elseif (defined('PHP_BINARY')) {
      $php_bin = PHP_BINARY;
    } else {
      $php_bin = 'php';
    }
    return $php_bin;
  }

}
