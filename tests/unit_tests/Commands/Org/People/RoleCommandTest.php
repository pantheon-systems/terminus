<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\People;

use Pantheon\Terminus\Commands\Org\People\RoleCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class RoleCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\People\RoleCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\People
 */
class RoleCommandTest extends OrgPeopleCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RoleCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the org:people:role command
     */
    public function testRole()
    {
        $email = 'devuser@pantheon.io';
        $org_name = 'org_name';
        $full_name = 'Dev User';
        $role = 'role';
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
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($org_name);
        $this->org_user_membership->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($full_name);

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
