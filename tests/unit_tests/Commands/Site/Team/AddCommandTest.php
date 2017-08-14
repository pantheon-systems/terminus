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
     * @var string
     */
    protected $message;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->message = 'message';

        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($this->message);

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
        $role = 'any_role';

        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(true);
        $this->user_memberships->expects($this->once())
            ->method('create')
            ->willReturn($this->workflow)
            ->with($new_member, $role);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message)
            );

        $out = $this->command->add('mysite', $new_member, $role);
        $this->assertNull($out);
    }

    /**
     * Tests the site:team:add command when change_management is not enabled
     */
    public function testAddCommandRestricted()
    {
        $new_member = 'test@example.com';
        $role = 'any_role';
        $default_role = 'team_member';

        $this->site->expects($this->once())
            ->method('getFeature')
            ->with('change_management')
            ->willReturn(false);
        $this->user_memberships->expects($this->once())
            ->method('create')
            ->willReturn($this->workflow)
            ->with($new_member, $default_role);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo('Site does not have change management enabled, defaulting to user role {role}.'),
                $this->equalTo(['role' => $default_role,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message)
            );

        $out = $this->command->add('mysite', $new_member, $role);
        $this->assertNull($out);
    }


    /**
     * Tests the site:team:add command when change_management is not enabled and the role param is not given
     */
    public function testAddCommandRestrictedNoRole()
    {
        $new_member = 'test@example.com';
        $default_role = 'team_member';

        $this->site->expects($this->never())
            ->method('getFeature');
        $this->user_memberships->expects($this->once())
            ->method('create')
            ->willReturn($this->workflow)
            ->with($new_member, $default_role);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($this->message)
            );

        $out = $this->command->add('mysite', $new_member);
        $this->assertNull($out);
    }
}
