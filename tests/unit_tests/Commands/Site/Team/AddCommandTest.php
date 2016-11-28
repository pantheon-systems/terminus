<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\Commands\Site\Team\AddCommand;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Team\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Team
 */
class AddCommandTest extends TeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the site:team:add command
     */
    public function testAddCommand()
    {
        $new_member = 'test@example.com';
        $message = 'message';
        $role = 'any_role';

        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(true);
        $this->user_memberships->expects($this->once())
            ->method('create')
            ->willReturn($this->workflow)
            ->with($new_member, $role);
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

        $out = $this->command->add('mysite', $new_member, $role);
        $this->assertNull($out);
    }
}
