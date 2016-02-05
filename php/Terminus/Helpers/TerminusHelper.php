<?php

namespace Terminus\Helpers;

abstract class TerminusHelper {

  /**
   * @var Logger
   */
  private $logger;

  /**
   * TerminusHelper constructor.
   *
   * @param array $options Options and dependencies for this helper
   * @return TerminusHelper $this
   */
  public function __construct(array $options = []) {
    $this->logger = $options['logger'];
  }

  /**
   * Returns the logger object for use
   *
   * @return Logger
   */
  protected function log() {
    return $this->logger;
  }

}