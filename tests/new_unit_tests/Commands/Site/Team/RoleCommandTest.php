<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\Site\Team\TeamCommandTest;
use Pantheon\Terminus\Commands\Site\Team\RoleCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Site\Team\RoleCommand
 */
class RoleCommandTest extends TeamCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new RoleCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the site:team:role command.
     */
    public function testRoleCommand()
    {
        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(true);
        $this->user_membership->expects($this->once())
            ->method('setRole')
            ->with('admin')
            ->willReturn($this->workflow);
        $this->command->role('mysite', 'test@example.com', 'admin');
    }
}
