<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Commands\Auth\LogoutCommand;

/**
 * Class LogoutCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Auth\LogoutCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class LogoutCommandTest extends AuthTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new LogoutCommand();
        $this->command->setConfig($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the auth:logout command
     */
    public function testLogInWithMachineToken()
    {
        $this->session->expects($this->once())
            ->method('destroy')
            ->with();
        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('notice'), $this->equalTo('You have been logged out of Pantheon.'));

        $out = $this->command->logOut();
        $this->assertNull($out);
    }
}
