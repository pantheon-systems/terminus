<?php

namespace Terminus;

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class Configurator {
  /**
   * @var string[]
   */
  private $config = [];
  /**
   * @var string
   */
  private $config_path = '/config/constants.yml';
  /**
   * @var string
   */
  private $constant_prefix = 'TERMINUS_';

  /**
   * Constructs config, configures
   */
  public function construct() {
    $this->configure();
    $this->spec = include TERMINUS_ROOT . '/config/spec.php';

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
   * Returns the appropriate home directory.
   *
   * Adapted from Terminus Package Manager by Ed Reel
   * @author Ed Reel <@uberhacker>
   * @url    https://github.com/uberhacker/tpm
   *
   * @return string
   */
  public static function getHomeDir() {
    $home = getenv('HOME');
    if (!$home) {
      $system = '';
      if (getenv('MSYSTEM') !== null) {
        $system = strtoupper(substr(getenv('MSYSTEM'), 0, 4));
      }
      if ($system != 'MING') {
        $home = getenv('HOMEPATH');
      }
    }
    return $home;
  }

  /**
   * Returns a configuration setting
   *
   * @param $key The key of the config setting to return
   * @return string $this->config[$property]
   */
  public static function get($key) {
    return $this->config[$key];
  }

  /**
   * Returns all configuration settings
   *
   * @return string[] $this->config
   */
  public static function getAll() {
    return $this->config;
  }

  /**
   * Ensures that directory paths work in any system
   *
   * @param string $path A path to set the directory separators for
   * @return string
   */
  public static function fixDirectorySeparators($path) {
    $fixed_path = str_replace(
      ['/', '\\',],
      DIRECTORY_SEPARATOR,
      $path
    );
    return $fixed_path;
  }

  /**
   * Returns location of PHP with which to run Terminus
   *
   * @return string
   */
  private function getPhpBinary() {
    if (getenv('TERMINUS_PHP')) {
      $php_bin = getenv('TERMINUS_PHP');
    } elseif (defined('PHP_BINARY')) {
      $php_bin = PHP_BINARY;
    } else {
      $php_bin = 'php';
    }
    return $php_bin;
  }

  /**
   * Finds and returns the root directory of Terminus
   *
   * @param string $current_dir Directory to start searching at
   * @return string
   */
  private function getTerminusRoot($current_dir = null) {
    if (defined('TERMINUS_ROOT')) {
      return TERMINUS_ROOT;
    }

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
    if (defined('TERMINUS_SCRIPT')) {
      return TERMINUS_SCRIPT;
    }

    $debug           = debug_backtrace();
    $script_location = array_pop($debug);
    $script_name     = str_replace(
      $this->config['root'] . '/',
      '',
      $script_location['file']
    );
    return $script_name;
  }

  /**
   * Sets constants necessary for the proper functioning of Terminus
   *
   * @return void
   */
  private function configure() {
    $this->importEnvironmentVariables();
    $config = [
      'root'   => $this->getTerminusRoot(),
      'php'    => $this->getPhpBinary(),
      'script' => $this->getTerminusScript(),
    ];
    $this->config = $config;

    $file_config = Yaml::parse(
      file_get_contents($config['root'] . $this->config_path)
    );
    foreach ($file_config as $name => $setting) {
      if (defined($name)) {
        $setting = $constant['echo'];
      } else if (isset($_SERVER[$name]) && ($_SERVER[$name] != '')) {
        $setting = $_SERVER[$name];
      } else if (getenv($name)) {
        $setting = getenv($name);
      }
      $key = strtolower(str_replace($this->constant_prefix, '', $name));
      $this->config[$key] = $this->replacePlaceholders($setting);
    }
  }

  /**
   * Imports environment variables
   *
   * @return void
   */
  private function importEnvironmentVariables() {
    //Load environment variables from __DIR__/.env
    if (file_exists(getcwd() . '/.env')) {
      $env = new Dotenv(getcwd());
      $env->load();
    }
  }

  /**
   * Exchanges values in [[ ]] in the given string with constants
   *
   * @param string $string The string to perform replacements on
   * @return string $string The modified string
   */
  private function replacePlaceholders($string) {
    $regex = '~\[\[(.*?)\]\]~';
    preg_match_all($regex, $string, $matches);
    if (!empty($matches)) {
      foreach ($matches[1] as $id => $value) {
        $replacement_key = trim($value);
        if (isset($this->config[$replacement_key])) {
          $replacement = $this->config[$replacement_key];
          $string = str_replace($matches[0][$id], $replacement, $string);
        }
      }
    }
    $fixed_string = self::fixDirectorySeparators(
      str_replace('~', self::getHomeDir(), $string)
    );
    return $fixed_string;
  }

}
