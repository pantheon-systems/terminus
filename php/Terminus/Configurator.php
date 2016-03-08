<?php

namespace Terminus;

use Symfony\Component\Yaml\Yaml;

class Configurator {
  private $config = [];
  private $spec;
  private $special_flags = ['--no-cache-clear',];

  /**
   * Constructs configurator, configures
   *
   * @param string $path Path to configuration specification file
   */
  public function __construct($path = null) {
    $this->importEnvironmentVariables();
    $this->defineConstants();

    if (is_null($path)) {
      $path = TERMINUS_ROOT . '/config/spec.php';
    }
    $this->spec = include $path;

    $defaults = [
      'runtime'  => false,
      'file'     => false,
      'synopsis' => '',
      'default'  => null,
      'multiple' => false,
    ];

    foreach ($this->spec as $key => $details) {
      $this->spec[$key]   = array_merge($defaults, $details);
      $this->config[$key] = $details['default'];
    }
  }

  /**
   * Splits positional args from associative args.
   *
   * @param array $arguments Arguments to parse
   * @return array
   */
  public function extractAssoc(array $arguments) {
    $positional_args = $assoc_args = [];

    foreach ($arguments as $arg) {
      if (in_array($arg, $this->special_flags)) {
        $assoc_args[] = [str_replace('--', '', $arg), null,];
      } elseif (preg_match('|^--no-([^=]+)$|', $arg, $matches)) {
        $assoc_args[] = [$matches[1], false,];
      } elseif (preg_match('|^--([^=]+)$|', $arg, $matches)) {
        $assoc_args[] = [$matches[1], true,];
      } elseif (preg_match('|^--([^=]+)=(.+)|s', $arg, $matches)) {
        $assoc_args[] = [$matches[1], $matches[2],];
      } else {
        $positional_args[] = $arg;
      }
    }

    $array = [$positional_args, $assoc_args,];
    return $array;
  }

  /**
   * Get configuration specification, i.e. list of accepted keys.
   *
   * @return array`
   */
  public function getSpec() {
    return $this->spec;
  }

  /**
   * Adds the given array to the config property array
   *
   * @param array $config Details to add to config
   * @return void
   */
  public function mergeArray($config) {
    foreach ($this->spec as $key => $details) {
      if (($details['runtime'] !== false) && isset($config[$key])) {
        $value = $config[$key];

        if ($details['multiple']) {
          $value = $this->arrayify($value);
          $this->config[$key] = array_merge($this->config[$key], $value);
        } else {
          $this->config[$key] = $value;
        }
      }
    }
  }

  /**
   * Splits a list of arguments into positional, associative and config.
   *
   * @param array $arguments Arguments to parse
   * @return array positional_args, assoc_args, runtime_config
   */
  public function parseArgs($arguments) {
    list($positional_args, $mixed_args) = $this->extractAssoc($arguments);
    list($assoc_args, $runtime_config)  = $this->unmixAssocArgs($mixed_args);
    $array = [$positional_args, $assoc_args, $runtime_config,];
    return $array;
  }

  /**
   * Returns the config property
   *
   * @return array
   */
  public function toArray() {
    return $this->config;
  }

  /**
   * Puts the given value into an array, if it is not already
   *
   * @param mixed $val Value to put in an array
   * @return array
   */
  private function arrayify($val) {
    if (!is_array($val)) {
      $val = [$val,];
    }
    return $val;
  }

  /**
   * Sets constants necessary for the proper functioning of Terminus
   *
   * @return void
   */
  private function defineConstants() {
    if (!defined('TERMINUS_ROOT')) {
      define('TERMINUS_ROOT', $this->getTerminusRoot());
    }
    if (!defined('Terminus')) {
      define('Terminus', true);
    }
    $default_constants = Yaml::parse(
      file_get_contents(TERMINUS_ROOT . '/config/constants.yml')
    );
    foreach ($default_constants as $var_name => $default) {
      if (!defined($var_name)) {
        if (isset($_SERVER[$var_name]) && ($_SERVER[$var_name] != '')) {
          define($var_name, $_SERVER[$var_name]);
        } else if (!defined($var_name)) {
          define($var_name, $default);
        }
      }
    }
    date_default_timezone_set(TERMINUS_TIME_ZONE);
    if (!defined('TERMINUS_SCRIPT')) {
      define('TERMINUS_SCRIPT', $this->getTerminusScript());
    }
  }

  /**
   * Finds and returns the root directory of Terminus
   *
   * @param string $current_dir Directory to start searching at
   * @return string
   */
  private function getTerminusRoot($current_dir = null) {
    if (is_null($current_dir)) {
      $current_dir = dirname(__DIR__);
    }
    if (file_exists("$current_dir/composer.json")) {
      return $current_dir;
    }
    $dir = explode('/', $current_dir);
    array_pop($dir);
    if (empty($dir)) {
      throw new TerminusError("Could not locate root to set TERMINUS_ROOT.");
    }
    $dir = implode('/', $dir);
    $root_dir = $this->getTerminusRoot($dir);
    return $root_dir;
  }

  /**
   * Finds and returns the name of the script running Terminus functions
   *
   * @return string
   */
  private function getTerminusScript() {
    $debug           = debug_backtrace();
    $script_location = array_pop($debug);
    $script_name     = str_replace(
      TERMINUS_ROOT . '/',
      '',
      $script_location['file']
    );
    return $script_name;
  }

  /**
   * Imports environment variables
   *
   * @return void
   */
  private function importEnvironmentVariables() {
    //Load environment variables from __DIR__/.env
    if (file_exists(getcwd() . '/.env')) {
      $env = new \Dotenv\Dotenv(getcwd());
      $env->load();
    }
  }

  /**
   * Separates assoc_args from runtime configuration
   *
   * @param array $mixed_args A mixture of runtime args and command args
   * @return array [0] = assoc_args, [1] = runtime_config
   */
  private function unmixAssocArgs($mixed_args) {
    $assoc_args = $runtime_config = [];

    foreach ($mixed_args as $tmp) {
      list($key, $value) = $tmp;

      if (isset($this->spec[$key]) && $this->spec[$key]['runtime'] !== false) {
        $details = $this->spec[$key];

        if (isset($details['deprecated'])) {
          fwrite(
            STDERR,
            "Terminus: The --$key global parameter is deprecated. "
            . $details['deprecated'] . "\n"
          );
        }

        if ($details['multiple']) {
          $runtime_config[$key][] = $value;
        } else {
          $runtime_config[$key] = $value;
        }
      } else {
        $assoc_args[$key] = $value;
      }
    }

    return [$assoc_args, $runtime_config,];
  }

}

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/dispatcher.php';
