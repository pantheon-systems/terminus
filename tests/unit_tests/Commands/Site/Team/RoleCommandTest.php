<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\Commands\Site\Team\RoleCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class RoleCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Team\RoleCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Team
 */
class RoleCommandTest extends TeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new RoleCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the site:team:role command
     */
    public function testRoleCommand()
    {
        $message = 'message';
        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(true);
        $this->user_membership->expects($this->once())
            ->method('setRole')
            ->with('admin')
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

        $out = $this->command->role('mysite', 'test@example.com', 'admin');
        $this->assertNull($out);
    }

    /**
     * Tests the site:team:role command when the site cannot change management
     */
    public function testRoleCommandRestricted()
    {
        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(false);
        $this->user_membership->expects($this->never())
            ->method('setRole');
        $this->workflow->expects($this->never())
            ->method('checkProgress');
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->never())
            ->method('log');

        $this->setExpectedException(
            TerminusException::class,
            'This site does not have its change-management option enabled.'
        );

        $out = $this->command->role('mysite', 'test@example.com', 'admin');
        $this->assertNull($out);
    }
}
