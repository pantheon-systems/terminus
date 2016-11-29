<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\Commands\Site\Team\RemoveCommand;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Team\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Team
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
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the site:team:remove command
     */
    public function testRemoveCommand()
    {
        $message = 'message';

        $this->user_membership->expects($this->once())
            ->method('delete')
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($message)
            );

        $out = $this->command->remove('mysite', 'test@example.com');
        $this->assertNull($out);
    }
}
