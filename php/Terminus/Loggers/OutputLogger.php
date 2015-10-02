<?php
/**
 * @file
 * Contains Terminus\Loggers\ConsoleLogger
 */


namespace Terminus\Loggers;


use Psr\Log\AbstractLogger;
use Terminus\Outputters\OutputterInterface;

/**
 * Class OutputLogger
 * @package Terminus\Loggers
 */
class OutputLogger extends AbstractLogger {

  /**
   * @var OutputterInterface
   */
  protected $outputter;

  /**
   * @param OutputterInterface $outputter
   */
  public function __construct(OutputterInterface $outputter) {
    $this->setOutputter($outputter);
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
    $this->getOutputter()->outputMessage($level, $message, $context);
  }

  /**
   * @return OutputterInterface
   */
  public function getOutputter() {
    return $this->outputter;
  }

  /**
   * @param OutputterInterface $outputter
   */
  public function setOutputter($outputter) {
    $this->outputter = $outputter;
  }
}