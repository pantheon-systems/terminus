<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Team;

use Pantheon\Terminus\Commands\Org\Team\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Team\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Team
 */
class ListCommandTest extends OrgTeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:team:list command when the organization has no team members
     */
    public function testOrgTeamListEmpty()
    {
        $org_name = 'org_name';
        $this->org_user_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([]);
        $this->org_user_membership->expects($this->never())
            ->method('serialize');
        $this->organization->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)['name' => $org_name,]);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{org} has no team members.'),
                $this->equalTo(['org' => $org_name,])
            );

        $out = $this->command->listTeam($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the org:team:list command
     */
    public function testOrgTeamListNotEmpty()
    {
        $data = [
            'id' => 'user_id',
            'first_name' => 'Dev',
            'last_name' => 'User',
            'email' => 'devuser@pantheon.io',
            'role' => 'team_role',
        ];

        $this->org_user_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->org_user_membership,]);
        $this->org_user_membership->expects($this->any())
            ->method('serialize')
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listTeam($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([$data,], $out->getArrayCopy());
    }
}
