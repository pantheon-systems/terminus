<?php

use Terminus;
use Terminus\Runner;

class RunnerTest extends PHPUnit_Framework_TestCase {

  public function testRunCommand() {
    $runner = Terminus::getRunner();
    $this->assertInstanceOf('\Terminus\Runner', $runner);
    $args = array('site');
    $assoc_args = array('site' => 'phpunittest');
    $return = $runner->runCommand($args, $assoc_args);
    $this->assertNull($return);
  }

}
