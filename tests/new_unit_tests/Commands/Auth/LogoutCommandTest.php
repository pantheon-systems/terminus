<?php


namespace Pantheon\Terminus\UnitTests\Commands\Auth;


use Pantheon\Terminus\Commands\Auth\LogoutCommand;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class LogoutCommandTest extends CommandTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->command = new LogoutCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     */
    public function testLogout()
    {
        $this->session->expects($this->once())
            ->method('logOut');

        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('You have been logged out of Pantheon.')
            );

        $this->command->logOut();
    }
}
