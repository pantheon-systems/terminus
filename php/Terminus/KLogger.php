<?php

namespace Terminus;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

//TODO: Change this classname to Logger once the old logger is fully replaced
class KLogger extends Logger {
  protected $parent;

  /**
   * Class constructor. Feeds in output destination from env vars
   *
   * @param [array]  $options           Options for operation of logger
   *        [array] config Configuration options from Runner
   * @param [string] $logDirectory      File path to the logging directory
   * @param [string] $logLevelThreshold The LogLevel Threshold
   * @return [KLogger] $this
   */
  public function __construct(
    array $options = array(),
    $logDirectory = 'php://stderr',
    $logLevelThreshold = LogLevel::INFO
  ) {
    $config = $options['config'];
    unset($options['config']);

    if (isset($_SERVER['TERMINUS_LOG_DIR'])) {
      $logDirectory = $_SERVER['TERMINUS_LOG_DIR'];
    } elseif ($config['silent']) {
      $logDirectory = ini_get('error_log');
    }

    if ($config['debug']) {
      $logLevelThreshold = LogLevel::DEBUG;
    }

    if (!isset($options['logFormat'])) {
      if ($config['json'] != null) {
        $options['logFormat'] = 'json';
      }
      if ($config['bash'] != null) {
        $options['logFormat'] = 'bash';
      }
    }

    parent::__construct($logDirectory, $logLevelThreshold, $options);
    $this->parent = $this->extractParent();
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
    $parent = $this->parent;
    if ($parent->logLevels[$parent->logLevelThreshold] < $parent->logLevels[$level]) {
      return;
    }
    if ($parent->options['logFormat'] == 'json') {
      $message = $this->formatJsonMessages($level, $message, $context);
    } elseif ($parent->options['logFormat'] == 'bash') {
      $message = $this->formatBashMessages($level, $message, $context);
    } else {
      $message = $this->formatMessages($level, $message, $context);
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
   * Extracts private data from the parent class
   *
   * @return [stdClass] $parent
   */
  private function extractParent() {
    $array  = (array)$this;
    array_shift($array);
    $parent = new \stdClass();
    foreach ($array as $key => $value) {
      //All these keys begin with a null. We need to cut them off so they can be used.
      $property_name = substr(str_replace('Katzgrau\KLogger\Logger', '', $key), 2);
      $parent->$property_name = $value;
    }
    return $parent;
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
  private function formatMessages($level, $message, $context) {
    $parent = $this->parent;
    if ($parent->options['logFormat']) {
      $parts   = $this->getMessageParts($level, $message, $context);
      $message = $parent->options['logFormat'];
      foreach ($parts as $part => $value) {
        $message = str_replace('{'.$part.'}', $value, $message);
      }
    } else {
      $message = "[{$this->getTimestamp()}] [{$level}] {$message}";
    }
    if ($parent->options['appendContext'] && ! empty($context)) {
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
    $date_format = 'Y-m-d H:i:s';
    if (isset($this->options['dateFormat'])) {
      $date_format = $this->options['dateFormat'];
    }
    $date = date($date_format);
    return $date;
  }

}
