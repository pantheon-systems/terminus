<?php

namespace Pantheon\Terminus\UnitTests\Commands;


use Pantheon\Terminus\Session\Session;
use Psr\Log\NullLogger;
use Terminus\Collections\SshKeys;
use Terminus\Models\User;

/**
 * @property \PHPUnit_Framework_MockObject_MockObject ssh_keys
 */
abstract class SSHKeysCommandTest extends CommandTestCase {
  protected $session;
  protected $ssh_keys;
  protected $user;
  protected $logger;
  protected $command;

  /**
   * Sets up the fixture, for example, open a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $this->ssh_keys = $this->getMockBuilder(SshKeys::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->user = $this->getMockBuilder(User::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->user->ssh_keys = $this->ssh_keys;

    $this->session = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->session->method('getUser')
      ->willReturn($this->user);

    $this->logger = $this->getMockBuilder(NullLogger::class)
      ->setMethods(array('log'))
      ->getMock();


  }
}
