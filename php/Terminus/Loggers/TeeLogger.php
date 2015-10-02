<?php
/**
 * @file
 * Contains Terminus\Loggers\TeeLogger
 */


namespace Terminus\Loggers;


use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class TeeLogger
 * @package Terminus\Loggers
 */
class TeeLogger extends AbstractLogger {

  /**
   * @var LoggerInterface[]
   */
  protected $loggers = array();

  /**
   * @var array
   */
  protected $levels = array();

  /**
   * @param LoggerInterface $logger
   * @param $level
   */
  public function addLogger(LoggerInterface $logger, $level = LogLevel::INFO) {
    $this->levels[] = $level;
    $this->loggers[] = $logger;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   * @return null
   */
  public function log($level, $message, array $context = array()) {
    foreach ($this->loggers as $i => $logger) {
      // Log if the message is at or above this loggers threshold.
      if ($this->levelCompare($level, $this->levels[$i]) >= 0) {
        $logger->log($level, $message, $context);
      }
    }
  }

  /**
   * Compare to logging levels
   *
   * @param $a
   * @param $b
   * @return int
   */
  protected function levelCompare($a, $b) {
    $levels = array(
      LogLevel::EMERGENCY => 7,
      LogLevel::ALERT     => 6,
      LogLevel::CRITICAL  => 5,
      LogLevel::ERROR     => 4,
      LogLevel::WARNING   => 3,
      LogLevel::NOTICE    => 2,
      LogLevel::INFO      => 1,
      LogLevel::DEBUG     => 0
    );

    $a = $levels[$a];
    $b = $levels[$b];

    // Poor man's spaceship operator (<=>)
    if ($a < $b) {
      return -1;
    }
    if ($a > $b) {
      return 1;
    }
    return 0;
  }
}