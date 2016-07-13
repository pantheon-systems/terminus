<?php

use Terminus\Helpers\RequirementsHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\UpdateHelper
 */
class RequirementsHelperTest extends PHPUnit_Framework_TestCase {
    /**
     * @var RequirementsHelper
     */
  protected $requirements_helper;

  public function setUp() {
    $this->requirements_helper = new RequirementsHelper();
  }

    /**
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage SSL version is {version}, a minimum version of {min_version} is required.
     */
  public function testRejectsOpenSslLessThanMinimum() {
    // Version 0.9.6b beta 3
    $this->requirements_helper->hasMinimumSsl('9461795');
  }

  public function testAcceptsProperOpenSslVersion() {
    // Version 1.0.1c
    $this->assertTrue($this->requirements_helper->hasMinimumSsl('268439615'));
  }

    /**
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Error: Terminus requires PHP {min_php} or newer. You are running version {version}.
     */
  public function testRejectsPhpVersionLessThanMinimum() {
    $this->requirements_helper->hasMinimumPhp('5.3.3');
  }

  public function testAcceptsProperPhpVersion() {
    $this->assertTrue($this->requirements_helper->hasMinimumPhp('5.6.0'));
  }

}
