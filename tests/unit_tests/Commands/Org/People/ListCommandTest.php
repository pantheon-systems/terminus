<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\People;

use Pantheon\Terminus\Commands\Org\People\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\People\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\People
 */
class ListCommandTest extends OrgPeopleCommandTest
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
     * Tests the org:people:list command when the organization has no members
     */
    public function testOrgPeopleListEmpty()
    {
        $org_name = 'org_name';
        $this->org_user_memberships->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($org_name);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{org} has no members.'),
                $this->equalTo(['org' => $org_name,])
            );

        $out = $this->command->listPeople($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the org:people:list command
     */
    public function testOrgPeopleListNotEmpty()
    {
        $data = [
            'user_id' => [
                'id' => 'user_id',
                'first_name' => 'Dev',
                'last_name' => 'User',
                'email' => 'devuser@pantheon.io',
                'role' => 'team_role',
            ]
        ];

        $this->org_user_memberships->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listPeople($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
