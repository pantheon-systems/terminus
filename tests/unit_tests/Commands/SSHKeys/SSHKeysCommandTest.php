<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Collections\SshKeys;
use Pantheon\Terminus\Models\User;

/**
 * @property \PHPUnit_Framework_MockObject_MockObject ssh_keys
 */
abstract class SSHKeysCommandTest extends CommandTestCase
{
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
        parent::setUp();

        $this->ssh_keys = $this->getMockBuilder(SshKeys::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->expects($this->any())
            ->method('getSshKeys')
            ->willReturn($this->ssh_keys);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->method('getUser')
            ->willReturn($this->user);
    }
}
