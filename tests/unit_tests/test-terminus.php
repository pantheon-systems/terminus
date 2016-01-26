<?php

/**
 * Testing class for Terminus
 */
class TerminusTest extends PHPUnit_Framework_TestCase {

  public function testIsTest() {
    $this->assertTrue(Terminus::isTest());
  }

}
