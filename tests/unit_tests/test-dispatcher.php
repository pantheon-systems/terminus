<?php

use Terminus\Dispatcher;

/**
 * Testing class for Terminus\Dispatcher
 */
class DispatcherTest extends PHPUnit_Framework_TestCase {

  public function testGetPath() {
    $root_command = Terminus::getRootCommand();
    $path         = Dispatcher\getPath($root_command);
    $this->assertEquals($path, ['terminus']);
  }

}
