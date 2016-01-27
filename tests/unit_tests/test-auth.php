<?php

use Terminus\Auth;

/**
 * Testing class for Terminus\Auth
 */
class AuthTest extends PHPUnit_Framework_TestCase {

  function testGetMachineTokenCreationUrl() {
    $url = Auth::getMachineTokenCreationUrl();
    $this->assertInternalType('string', $url);
    $this->assertInternalType('integer', strpos($url, 'machine-token/create'));
  }

}
