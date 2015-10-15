<?php

use \Terminus\Runner;
use \Terminus;

class RunnerTest extends PHPUnit_Framework_TestCase {

  function testRunCommand() {
    $runner = Terminus::getRunner();
    $this->assertInstanceOf('\Terminus\Runner',$runner);
    $args = array('site');
    $assoc_args = array('site' => 'phpunittest');
    $return = $runner->runCommand($args, $assoc_args);
    // @todo null here is expected 
    $this->assertNull($return);
  }

}
