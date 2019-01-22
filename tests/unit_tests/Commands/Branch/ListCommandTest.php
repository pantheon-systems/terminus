<?php

namespace Pantheon\Terminus\UnitTests\Commands\Branch;

use Pantheon\Terminus\Commands\Branch\ListCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Models\Branch;

/**
 * Class ListCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Branch\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Branch
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * Tests the branch:list command
     */
    public function testListBranches()
    {
        $branches_info = [
            'master' => ['id' => 'master', 'sha' => 'xxx'],
            'another' => ['id' => 'another', 'sha' => 'yyy'],
        ];
        
        $branches_collection = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $branches_collection->expects($this->once())
            ->method('serialize')
            ->willReturn($branches_info);
        $branches_collection->method('getCollectedClass')->willReturn(Branch::class);

        $this->site->expects($this->once())
            ->method('getBranches')
            ->willReturn($branches_collection);

        $command = new ListCommand();
        $command->setSites($this->sites);
        $out = $command->listBranches('my-site');
        $this->assertEquals($branches_info, $out->getArrayCopy());
    }
}
