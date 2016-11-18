<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Team;

use Pantheon\Terminus\Commands\Org\Team\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Team\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Team
 */
class RemoveCommandTest extends OrgTeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:team:remove command
     */
    public function testRemove()
    {
        $email = 'devuser@pantheon.io';
        $org_name = 'org_name';
        $full_name = 'Dev User';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->org_user_memberships->expects($this->once())
            ->method('get')
            ->with($email)
            ->willReturn($this->org_user_membership);
        $this->org_user_membership->expects($this->once())
            ->method('delete')
            ->with()
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
                $this->equalTo('{member} has been removed from the {org} organization.'),
                $this->equalTo(['member' => $full_name, 'org' => $org_name,])
            );

        $out = $this->command->remove($this->organization->id, $email);
        $this->assertNull($out);
    }
}
