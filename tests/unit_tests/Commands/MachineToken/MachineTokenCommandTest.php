<?php

namespace Pantheon\Terminus\UnitTests\Commands\MachineToken;

use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Psr\Log\NullLogger;
use Pantheon\Terminus\Collections\MachineTokens;

/**
 * Class MachineTokenCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
abstract class MachineTokenCommandTest extends CommandTestCase
{
    protected $session;
    protected $machine_tokens;
    protected $user;
    protected $logger;
    protected $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->machine_tokens = $this->getMockBuilder(MachineTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->expects($this->any())
            ->method('getMachineTokens')
            ->willReturn($this->machine_tokens);

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
