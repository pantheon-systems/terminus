<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\Site\Team\TeamCommandTest;
use Pantheon\Terminus\Commands\Site\Team\RemoveCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Site\Team\RemoveCommand
 */
class RemoveCommandTest extends TeamCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the site:team:remove command.
     */
    public function testRemoveCommand()
    {
        $this->user_membership->expects($this->once())
            ->method('delete')
            ->willReturn($this->workflow);
        $this->command->remove('mysite', 'test@example.com');
    }
}
