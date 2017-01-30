<?php

namespace Pantheon\Terminus\UnitTests\Commands\SSHKey;

use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class SSHKeyCommandTest
 * Testing class for Pantheon\Terminus\Commands\SSHKey\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\SSHKey
 */
abstract class SSHKeyCommandTest extends CommandTestCase
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var SSHKeys
     */
    protected $ssh_keys;
    /**
     * @var User
     */
    protected $user;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ssh_keys = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->method('getUser')
            ->willReturn($this->user);
        $this->user->expects($this->any())
            ->method('getSSHKeys')
            ->willReturn($this->ssh_keys);
    }
}
