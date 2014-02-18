<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Event\SuiteEvent;

use \Terminus\Utils;

require_once __DIR__ . '/../../php/utils.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext implements ClosuredContextInterface {

  public $parameters = array();

  public function __construct(array $parameters) {
      $this->parameters = $parameters;

      if (file_exists(__DIR__ . '/../support/env.php')) {
          $world = $this;
          require(__DIR__ . '/../support/env.php');
      }
  }

  public function getStepDefinitionResources() {
      if (file_exists(__DIR__ . '/../steps')) {
          return glob(__DIR__.'/../steps/*.php');
      }
      return array();
  }

  public function getHookDefinitionResources() {
      if (file_exists(__DIR__ . '/../support/hooks.php')) {
          return array(__DIR__ . '/../support/hooks.php');
      }
      return array();
  }

  public function __call($name, array $args) {
      if (isset($this->$name) && is_callable($this->$name)) {
          return call_user_func_array($this->$name, $args);
      } else {
          $trace = debug_backtrace();
          trigger_error(
              'Call to undefined method ' . get_class($this) . '::' . $name .
              ' in ' . $trace[0]['file'] .
              ' on line ' . $trace[0]['line'],
              E_USER_ERROR
          );
      }
  }

  public function replace_variables( $str ) {
    return preg_replace_callback( '/\{([A-Z_]+)\}/', array( $this, '_replace_var' ), $str );
  }

  private function _replace_var( $matches ) {
    $cmd = $matches[0];

    foreach ( array_slice( $matches, 1 ) as $key ) {
      $cmd = str_replace( '{' . $key . '}', $this->variables[ $key ], $cmd );
    }

    return $cmd;
  }

  public function create_run_dir() {
    if ( !isset( $this->parameters['RUN_DIR'] ) ) {
      $this->parameters['RUN_DIR'] = sys_get_temp_dir() . '/' . uniqid( "terminus-test-run-", TRUE );
      mkdir( $this->parameters['RUN_DIR'] );
    }
  }

  public function proc( $command, $assoc_args = array() ) {
    if ( !empty( $assoc_args ) )
      $command .= Utils\assoc_args_to_str( $assoc_args );

    return Process::create( $command, $this->parameters['RUN_DIR'], array() );
  }


}
