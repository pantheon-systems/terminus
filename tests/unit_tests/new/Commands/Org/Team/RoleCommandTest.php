<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Team;

use Pantheon\Terminus\Commands\Org\Team\RoleCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class RoleCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Team\RoleCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Team
 */
class RoleCommandTest extends OrgTeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RoleCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:team:role command
     */
    public function testRole()
    {
        $email = 'devuser@pantheon.io';
        $org_name = 'org_name';
        $full_name = 'Dev User';
        $role = 'team_role';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->org_user_memberships->expects($this->once())
            ->method('get')
            ->with($email)
            ->willReturn($this->org_user_membership);
        $this->org_user_membership->expects($this->once())
            ->method('setRole')
            ->with($this->equalTo($role))
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->willReturn(true);
        $this->organization->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)['name' => $org_name,]);
        $this->org_user_membership->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)compact('full_name'));

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo("{member}'s role has been changed to {role} in the {org} organization."),
                $this->equalTo(['member' => $full_name, 'role' => $role, 'org' => $org_name,])
            );

        $out = $this->command->role($this->organization->id, $email, $role);
        $this->assertNull($out);
    }
}
