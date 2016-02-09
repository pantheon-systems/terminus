<?php

namespace Terminus\Commands;

use Terminus\Caches\FileCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;
use Terminus\Outputters\OutputterInterface;
use Terminus\Utils;

/**
 * The base class for Terminus commands
 */
abstract class TerminusCommand {
  /**
   * @var FileCache
   */
  protected $cache;

  /**
   * @var Input
   */
  protected $inputter;

  /**
   * @var stdClass
   */
  protected $helpers;

  /**
   * @var Logger
   */
  protected $logger;

  /**
   * @var OutputterInterface
   */
  protected $outputter;

  /**
   * @var Session
   */
  protected $session;

  /**
   * @var Sites
   */
  protected $sites;

  /**
   * Instantiates object, sets cache and session
   *
   * @param array $options Elements as follow:
   *        FileCache cache
   *        Logger    Logger
   *        Outputter Outputter
   *        Session   Session
   * @return TerminusCommand
   */
  public function __construct(array $options = []) {
    $this->cache     = new FileCache();
    $this->logger    = $options['logger'];
    $this->outputter = $options['outputter'];
    $this->session   = $options['session'];
    $this->loadHelpers();

    if (!Utils\isTest()) {
      Utils\checkForUpdate($this->logger);
    }
  }

  /**
   * Sends the given message to logger as an error and exits with -1
   *
   * @param string $message   Message to log as error before exit
   * @param array  $context   Vars to interpolate in message
   * @param int    $exit_code Code to exit with
   * @return void
   * @throws TerminusException
   */
  protected function failure(
    $message       = 'Command failed',
    array $context = array(),
    $exit_code     = 1
  ) {
    throw new TerminusException($message, $context, $exit_code);
  }

  /**
   * Retrieves the input helper for use
   *
   * @return Input
   */
  protected function input() {
    return $this->helpers->input;
  }

  /**
   * Retrieves the logger for use
   *
   * @return Logger
   */
  protected function log() {
    return $this->logger;
  }

  /**
   * Retrieves the outputter for use
   *
   * @return OutputterInterface
   */
  protected function output() {
    return $this->outputter;
  }

  /**
   * Loads helper classes
   *
   * @return void
   */
  protected function loadHelpers() {
    if (isset($this->helpers)) {
      return;
    }
    $helpers_dir       = __DIR__ . '/../Helpers';
    $helpers_namespace = 'Terminus\\Helpers\\';

    Utils\loadDirectory($helpers_dir);
    $classes = get_declared_classes();
    $helpers = array_filter(
      $classes,
      function ($class) use ($helpers_namespace) {
        $reflection = new \ReflectionClass($class);
        $is_helper  = (
          (strpos($class, $helpers_namespace) === 0)
          && !$reflection->isAbstract()
        );
        return $is_helper;
      }
    );

    if (!empty($helpers)) {
      $options          = ['logger' => $this->log()];
      $helpers_property = new \stdClass();
      foreach ($helpers as $helper) {
        $property_name = strtolower(
          str_replace([$helpers_namespace, 'Helper'], '', $helper)
        );
        $helpers_property->$property_name = new $helper($options);
      }
    }
    $this->helpers = $helpers_property;
  }

  /**
   * Saves the logger object as a class property
   *
   * @param Logger $logger Logger object to save
   * @return void
   */
  protected function setLogger(Logger $logger) {
    $this->logger = $logger;
  }

  /**
   * Saves the outputter object as a class property
   *
   * @param OutputterInterface $outputter Outputter object to save
   * @return void
   */
  protected function setOutputter(OutputterInterface $outputter) {
    $this->outputter = $outputter;
  }

  /**
   * Outputs basic workflow success/failure messages
   *
   * @param Workflow $workflow Workflow to output message about
   * @return void
   */
  protected function workflowOutput($workflow) {
    if ($workflow->get('result') == 'succeeded') {
      $this->log()->info($workflow->get('active_description'));
    } else {
      $final_task = $workflow->get('final_task');
      $this->log()->error($final_task->reason);
    }
  }

}
