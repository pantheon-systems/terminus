<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\Site\Team\TeamCommandTest;
use Pantheon\Terminus\Commands\Site\Team\AddCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Site\Team\AddCommand
 */
class TeamCommandsTest extends TeamCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new AddCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the site:team:add command.
     */
    public function testAddCommand()
    {
        $new_member = 'test@example.com';
        $this->site->user_memberships->expects($this->once())
            ->method('create')
            ->willReturn($this->workflow)
            ->with($new_member, 'team_member');
        $this->command->add('mysite', $new_member);
    }
}
