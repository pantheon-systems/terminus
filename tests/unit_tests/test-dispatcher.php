<?php

use Terminus\Dispatcher;
use Terminus\Dispatcher\RootCommand;

/**
 * Testing class for Terminus\Dispatcher
 */
class DispatcherTest extends PHPUnit_Framework_TestCase {

  public function testGetPath() {
    $root_command = new RootCommand();
    $path         = Dispatcher\getPath($root_command);
    $this->assertEquals($path, ['terminus']);
  }

}
