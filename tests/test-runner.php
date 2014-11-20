<?php
use \Terminus\Runner;
use \Terminus;

class RunnerTest extends PHPUnit_Framework_TestCase {

  function testRunCommand() {
    $runner = Terminus::get_runner();
    $this->assertInstanceOf('\Terminus\Runner',$runner);
    $args = array('site');
    $assoc_args = array('site' => 'phpunittest');
    $return = $runner->run_command($args, $assoc_args);
    // @todo null here is expected 
    $this->assertNull($return);
  }

}
