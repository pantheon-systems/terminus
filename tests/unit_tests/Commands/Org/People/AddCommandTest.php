<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\People;

use Pantheon\Terminus\Commands\Org\People\AddCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\People\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\People
 */
class AddCommandTest extends OrgPeopleCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the org:people:add command
     */
    public function testAdd()
    {
        $email = 'devuser@pantheon.io';
        $role = 'user_role';
        $org_name = 'org_name';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->org_user_memberships->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($workflow);
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($org_name);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{email} has been added to the {org} organization as a(n) {role}.'),
                $this->equalTo(['email' => $email, 'org' => $org_name, 'role' => $role,])
            );

        $out = $this->command->add($this->organization->id, $email, $role);
        $this->assertNull($out);
    }
}
