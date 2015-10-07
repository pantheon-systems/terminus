<?php

namespace Terminus\Loggers;

use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LogLevel;

class Logger extends KLogger {

  /**
   * Class constructor. Feeds in output destination from env vars
   *
   * @param [array]  $options           Options for operation of logger
   *        [array] config Configuration options from Runner
   * @param [string] $logDirectory      File path to the logging directory
   * @param [string] $logLevelThreshold The LogLevel Threshold
   * @return [Logger] $this
   */
  public function __construct(
    array $options = array(),
    $logDirectory = 'php://stderr',
    $logLevelThreshold = LogLevel::INFO
  ) {
    $config = $options['config'];
    unset($options['config']);
    $options['dateFormat'] = 'Y-m-d H:i:s';

    if ($config['debug']) {
      $logLevelThreshold = LogLevel::DEBUG;
    }

    if (!isset($options['logFormat'])) {
      $options['logFormat'] = $config['format'];
    }

    if (isset($_SERVER['TERMINUS_LOG_DIR'])) {
      $logDirectory = $_SERVER['TERMINUS_LOG_DIR'];
    } elseif ($config['format'] == 'silent') {
      $logDirectory = ini_get('error_log');
      if ($logDirectory == '') {
        die(
          'You must either set error_log in your php.ini, or define '
          . ' TERMINUS_LOG_DIR to use silent mode.' . PHP_EOL
        );
      }
    }

    parent::__construct($logDirectory, $logLevelThreshold, $options);
  }

  /**
    * Logs with an arbitrary level.
    *
    * @param [mixed]  $level
    * @param [string] $message
    * @param [array]  $context
    * @return [void]
    */
  public function log($level, $message, array $context = array()) {
    if (
      isset($this->logLevelThreshold)
      && ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level])
    ) {
      return;
    }

    // Replace the context variables into the message per PSR spec:
    // https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
    $message = $this->interpolate($message, $context);


    if (isset($this->options) && $this->options['logFormat'] == 'json') {
      $message = $this->formatJsonMessages($level, $message, $context);
    } elseif (isset($this->options) && $this->options['logFormat'] == 'bash') {
      $message = $this->formatBashMessages($level, $message, $context);
    } else {
      $message = $this->formatMessage($level, $message, $context);
    }
    $this->write($message);
  }

  /**
   * Sets the output handle to php://std___
   *
   * @return [void]
   */
  public function setBufferHandle() {
    $handle_name = strtoupper(substr($this->getLogFilePath(), 6));
    $this->fileHandle = constant($handle_name);
  }

  /**
    * Formats the message for bash-type logging.
    *
    * @param  [string] $level   The Log Level of the message
    * @param  [string] $message The message to log
    * @param  [array]  $context The context
    * @return [string] $message
    */
  private function formatBashMessages($level, $message, $context) {
    $parts   = $this->getMessageParts($level, $message, $context);
    $message = '';
    foreach ($parts as $key => $value) {
      $message .= "$key\t$value\n";
    }
    return $message;
  }

  /**
    * Formats the message for JSON-type logging.
    *
    * @param  [string] $level   The Log Level of the message
    * @param  [string] $message The message to log
    * @param  [array]  $context The context
    * @return [string] $message
    */
  private function formatJsonMessages($level, $message, $context) {
    $parts   = $this->getMessageParts($level, $message, $context);
    $message = json_encode($parts) . "\n";
    return $message;
  }

  /**
    * Formats the message for logging.
    *
    * @param  [string] $level   The Log Level of the message
    * @param  [string] $message The message to log
    * @param  [array]  $context The context
    * @return [string] $message
    */
  protected function formatMessage($level, $message, $context) {
    if (
      isset($this->options)
      && in_array($this->options['logFormat'], array('bash', 'json'))
    ) {
      $parts   = $this->getMessageParts($level, $message, $context);
      $message = $this->options['logFormat'];
      foreach ($parts as $part => $value) {
        $message = str_replace('{'.$part.'}', $value, $message);
      }
    } else {
      $message = "[{$this->getTimestamp()}] [$level] $message";
    }
    if (
      isset($this->options)
      && $this->options['appendContext']
      && ! empty($context)
    ) {
      $message .= PHP_EOL . $this->indent($this->contextToString($context));
    }

    return $message . PHP_EOL;
  }

  /**
    * Collects and formats the log message parts
    *
    * @param  [string] $level   The Log Level of the message
    * @param  [string] $message The message to log
    * @param  [array]  $context The context
    * @return [string] $parts
    */
  private function getMessageParts($level, $message, $context) {
    $parts = array(
      'date'          => $this->getTimestamp(),
      'level'         => strtoupper($level),
      //'priority'      => $this->logLevels[$level],
      'message'       => $message,
      //'context'       => json_encode($context),
    );
    return $parts;
  }

  /**
   * Gets the correctly formatted Date/Time for the log entry.
   *
   * @return [string] $date
   */
  private function getTimestamp() {
    $date = date($this->options['dateFormat']);
    return $date;
  }

  /**
   * Interpolates context variables per the PSR spec.
   *
   * @param string $message The message containing replacements in the form {key}
   * @param array $context The array containing the values to be substituted.
   * @return string
   */
  private function interpolate($message, $context) {
    // build a replacement array with braces around the context keys
    $replace = array();
    foreach ($context as $key => $val) {
      $replace['{' . $key . '}'] = $val;
    }

    // interpolate replacement values into the message and return
    return strtr($message, $replace);
  }

}
