<?php

use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;
use Terminus\Outputters\OutputterInterface;
use Terminus\Utils;

/**
 * Various utilities for Terminus commands.
 */
class Terminus {
  /**
   * @var Logger
   */
  private static $logger;
  /**
   * @var array
   */
  private static $options;
  /**
   * @var OutputterInterface
   */
  private static $outputter;

  /**
   * Object constructor. Sets properties.
   *
   * @param array $arg_options Options to override defaults
   */
  public function __construct(array $arg_options = array()) {
    $default_options = array(
      'runner'   => null,
      'colorize' => 'auto',
      'format'   => 'json',
      'debug'    => false,
      'yes'      => false,
      'output'   => 'php://stdout',
    );
    self::$options = $options = array_merge($default_options, $arg_options);

    $this->setLogger($options);
    $this->setOutputter($options['format'], $options['output']);
  }

  /**
   * Retrieves the config array or a single element from it
   *
   * @param string $key Hash key of element to retrieve from config
   * @return mixed
   */
  public static function getConfig($key = null) {
    $config = self::$options;
    if (isset($config[$key])) {
      $config = $config[$key];
    } elseif (!is_null($key)) {
      throw new TerminusException(
        'Unknown config option "{key}".',
        compact('key'),
        1
      );
    }
    return $config;
  }

  /**
   * Retrieves the instantiated logger
   *
   * @return Logger $logger
   */
  public static function getLogger() {
    return self::$logger;
  }

  /**
   * Retrieves the instantiated outputter
   *
   * @return OutputterInterface
   */
  public static function getOutputter() {
    return self::$outputter;
  }

  /**
   * Set the logger instance to a class property
   *
   * @param array $config Configuration options to send to the logger
   * @return void
   */
  public static function setLogger($config) {
    self::$logger = new Logger(compact('config'));
  }

  /**
   * Set the outputter instance to a class property
   *
   * @param string $format      Type of formatter to set on outputter
   * @param string $destination Where output will be written to
   * @return void
   */
  public static function setOutputter($format, $destination) {
    // Pick an output formatter
    if ($format == 'json') {
      $formatter = new Terminus\Outputters\JSONFormatter();
    } elseif ($format == 'bash') {
      $formatter = new Terminus\Outputters\BashFormatter();
    } else {
      $formatter = new Terminus\Outputters\PrettyFormatter();
    }

    // Create an output service.
    self::$outputter = new Terminus\Outputters\Outputter(
      new Terminus\Outputters\StreamWriter($destination),
      $formatter
    );
  }

}

if (!defined('TERMINUS_ROOT')) {
  define('TERMINUS_ROOT', dirname(__DIR__));
}

require_once TERMINUS_ROOT . '/php/utils.php';
Utils\defineConstants();
Utils\importEnvironmentVariables();
