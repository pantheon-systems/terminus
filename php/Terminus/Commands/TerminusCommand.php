<?php

namespace Terminus\Commands;

use Terminus\Caches\FileCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;
use Terminus\Models\Auth;
use Terminus\Outputters\OutputterInterface;
use Terminus\Session;
use Terminus\Utils;

/**
 * The base class for Terminus commands
 */
abstract class TerminusCommand {
  /**
   * @var Runner
   */
  public $runner;
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
   * @var Session
   */
  protected $session;
  /**
   * @var Sites
   */
  protected $sites;
  /**
   * @var Logger
   */
  private $logger;
  /**
   * @var Outputter
   */
  private $outputter;

  /**
   * Instantiates object, sets cache and session
   *
   * @param array $arg_options Elements as follow:
   *        FileCache cache
   *        Logger    Logger
   *        Outputter Outputter
   *        Session   Session
   * @return TerminusCommand
   */
  public function __construct(array $arg_options = []) {
    $default_options = [
      'require_login' => false,
      'runner'        => null,
    ];
    $options         = array_merge($default_options, $arg_options);
    if ($options['require_login']) {
      $this->ensureLogin();
    }
    $this->cache     = new FileCache();
    $this->runner    = $options['runner'];
    $this->session   = Session::instance();
    $this->logger    = $this->runner->getLogger();
    $this->outputter = $this->runner->getOutputter();
    $this->loadHelpers();

    if (!Utils\isTest()) {
      $this->helpers->update->checkForUpdate($this->log());
    }
  }

  /**
   * Retrieves the logger for use
   *
   * @return Logger
   * @non-command
   */
  public function log() {
    return $this->logger;
  }

  /**
   * Retrieves the outputter for use
   *
   * @return OutputterInterface
   * @non-command
   */
  public function output() {
    return $this->outputter;
  }

  /**
   * Ensures the user is logged in or errs.
   *
   * @return bool Always true
   * @throws TerminusException
   */
  protected function ensureLogin() {
    $auth   = new Auth();
    $tokens = $auth->getAllSavedTokenEmails();
    if (!$auth->loggedIn()) {
      if (count($tokens) === 1) {
        $email = array_shift($tokens);
        $auth->logInViaMachineToken(compact('email'));
      } else if (isset($_SERVER['TERMINUS_USER'])
       && $email = $_SERVER['TERMINUS_USER']
      ) {
        $auth->logInViaMachineToken(compact('email'));
      } else {
        $message  = 'You are not logged in. Run `auth login` to ';
        $message .= 'authenticate or `help auth login` for more info.';
        $this->failure($message);
      }
    }
    return true;
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
    array $context = [],
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

    $this->loadDirectory($helpers_dir);
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
      $options          = ['command' => $this];
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
   * @param array    $messages Messages to override workflow's defaults:
   *  string success Success message to override workflow default
   *  string failure Failure message to override workflow default
   * @return void
   */
  protected function workflowOutput($workflow, array $messages = []) {
    if ($workflow->get('result') == 'succeeded') {
      $message = $workflow->get('active_description');
      if (isset($messages['success'])) {
        $message = $messages['success'];
      }
      $this->log()->info($message);
    } else {
      $message = 'Workflow failed.';
      if (isset($messages['failure'])) {
        $message = $messages['failure'];
      } elseif (!is_null($final_task = $workflow->get('final_task'))) {
        $message = $final_task->reason;
      }
      $this->log()->error($message);
    }
  }

  /**
   * Includes all PHP files within a directory
   *
   * @param string $directory Directory to include PHP files from
   * @return void
   */
  private function loadDirectory($directory) {
    if ($directory && file_exists($directory)) {
      $iterator = new \DirectoryIterator($directory);
      foreach ($iterator as $file) {
        if ($file->isFile() && $file->isReadable() && $file->getExtension() == 'php') {
          include_once $file->getPathname();
        }
      }
    }
  }

}
