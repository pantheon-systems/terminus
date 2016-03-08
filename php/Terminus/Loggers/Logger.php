<?php

namespace Terminus\Loggers;

use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LogLevel;

class Logger extends KLogger {

  /**
   * A list of fields not to display values for in output
   * @var array
   */
  private $blacklist = ['password', 'machine_token'];

  /**
   * Class constructor. Feeds in output destination from env vars
   *
   * @param array  $options           Options for operation of logger
   *        [array] config Configuration options from Runner
   * @param string $logDirectory      File path to the logging directory
   * @param string $logLevelThreshold The LogLevel Threshold
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
        $message  = 'You must either set error_log in your php.ini, or define ';
        $message .= ' TERMINUS_LOG_DIR to use silent mode.' . PHP_EOL;
        die($message);
      }
    }

    parent::__construct($logDirectory, $logLevelThreshold, $options);
  }

  /**
    * Logs with an arbitrary level
    *
    * @param mixed  $level   PSR log level of message
    * @param string $message Message to give
    * @param array  $context Context of message
    * @return void
    */
  public function log($level, $message, array $context = array()) {
    if (isset($this->logLevelThreshold)
      && ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level])
    ) {
      return;
    }
    $context = $this->stripSensitiveData($context, $this->blacklist);

    // Replace the context variables into the message per PSR spec:
    // https://github.com/php-fig/fig-standards/blob/master/accepted/...
    //   ...PSR-3-logger-interface.md#12-message
    $message = $this->interpolate($message, $context);

    if (isset($this->options) && $this->options['logFormat'] == 'json') {
      $message = $this->formatJsonMessages($level, $message);
    } elseif (isset($this->options) && $this->options['logFormat'] == 'bash') {
      $message = $this->formatBashMessages($level, $message);
    } else {
      $message = $this->formatMessage($level, $message, $context);
    }
    $this->write($message);
  }

  /**
   * Returns the option with the key given
   *
   * @param string $key Key to look for in options property
   * @return mixed
   */
  public function getOptions($key = null) {
    $options = $this->options;
    if (is_null($key)) {
      return $options;
    }
    if (isset($options[$key])) {
      return $options[$key];
    }
    throw new TerminusException(
      'The logger has no option named "{key}".',
      compact('key'),
      1
    );
  }

  /**
    * Formats the message for logging.
    *
    * @param  string $level   The Log Level of the message
    * @param  string $message The message to log
    * @param  array  $context The context
    * @return string
    */
  protected function formatMessage($level, $message, $context) {
    if (isset($this->options)
      && in_array($this->options['logFormat'], array('bash', 'json'))
    ) {
      $parts   = $this->getMessageParts($level, $message);
      $message = $this->options['logFormat'];
      foreach ($parts as $part => $value) {
        $message = str_replace('{'.$part.'}', $value, $message);
      }
    } else {
      $message = "[{$this->getTimestamp()}] [$level] $message";
    }
    if (isset($this->options)
      && $this->options['appendContext']
      && ! empty($context)
    ) {
      $message .= PHP_EOL . $this->indent($this->contextToString($context));
    }

    return $message . PHP_EOL;
  }

  /**
    * Formats the message for bash-type logging.
    *
    * @param  string $level   The Log Level of the message
    * @param  string $message The message to log
    * @return string
    */
  private function formatBashMessages($level, $message) {
    $parts   = $this->getMessageParts($level, $message);
    $message = '';
    foreach ($parts as $key => $value) {
      $message .= "$key\t$value\n";
    }
    return $message;
  }

  /**
    * Formats the message for JSON-type logging.
    *
    * @param  string $level   The Log Level of the message
    * @param  string $message The message to log
    * @return string
    */
  private function formatJsonMessages($level, $message) {
    $parts   = $this->getMessageParts($level, $message);
    $message = json_encode($parts) . "\n";
    return $message;
  }

  /**
    * Collects and formats the log message parts
    *
    * @param  string $level   The Log Level of the message
    * @param  string $message The message to log
    * @return array
    */
  private function getMessageParts($level, $message) {
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
   * @return string $date
   */
  private function getTimestamp() {
    $date_format = 'Y-m-dTH:i:s';
    if (isset($this->options)) {
      $date_format = $this->options['dateFormat'];
    }
    $date = date($date_format);
    return $date;
  }

  /**
   * Interpolates context variables per the PSR spec
   *
   * @param string $message The message containing curly brace-enclosed keys
   * @param array  $context The array containing substitutionary values
   * @return string
   */
  private function interpolate($message, $context) {
    // build a replacement array with braces around the context keys
    $replace = array();
    foreach ($context as $key => $val) {
      $replace['{' . $key . '}'] = $val;
    }

    // interpolate replacement values into the message and return
    if (!is_string($message)) {
      $message = json_encode($message);
    }
    $interpolated_string = strtr($message, $replace);
    return $interpolated_string;
  }

  /**
   * Strips sensitive data out of the JSON printed in a request string
   *
   * @param array $request_data An array of request parameters to censor
   * @param array $blacklist    Array of string keys to remove from request
   * @return string Sensitive data-stripped version of $request_data
   */
  private function stripSensitiveData($request_data, $blacklist = []) {
    foreach ($request_data as $key => $value) {
      if (in_array($key, $blacklist)) {
        $request_data[$key] = '*****';
      } else if (is_array($value)) {
        $request_data[$key] = stripSensitiveData($value, $blacklist);
      }
    }
    return $request_data;
  }

}
