<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\Commands\Site\Team\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Team\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Team
 */
class ListCommandTest extends TeamCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the site:team:list command
     */
    public function testListCommand()
    {
        $expected = [
            'abc' => [
                'firstname' => 'Daisy',
                'lastname' => 'Duck',
                'email' => 'daisy@duck.com',
                'user_id' => 'abc',
                'role' => 'team_member',
            ],
            'def' => [
                'firstname' => 'Mickey',
                'lastname' => 'Mouse',
                'email' => 'mickey@mouse.com',
                'user_id' => 'def',
                'role' => 'team_member',
            ]
        ];

        $this->user_memberships->expects($this->once())
            ->method('serialize')
            ->willReturn($expected);

        $actual = $this->command->teamList('mysite');
        $this->assertEquals($expected, $actual->getArrayCopy());
    }
}
