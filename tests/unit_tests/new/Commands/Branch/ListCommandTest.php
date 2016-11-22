<?php

namespace Pantheon\Terminus\UnitTests\Commands\Branch;

use Pantheon\Terminus\Commands\Branch\ListCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Models\Branch;

class ListCommandTest extends CommandTestCase
{
    public function testListBranches()
    {
        $branches_info = [
            ['id' => 'master', 'sha' => 'xxx'],
            ['id' => 'another', 'sha' => 'yyy'],
        ];

        $branches = [];
        foreach ($branches_info as $branch_info) {
            $branch = $this->getMockBuilder(Branch::class)
                ->disableOriginalConstructor()
                ->getMock();
            $branch->expects($this->once())
                ->method('serialize')
                ->willReturn($branch_info);
            $branches[] = $branch;
        }

        $branches_collection = $this->getMockBuilder(Branches::class)
            ->disableOriginalConstructor()
            ->getMock();
        $branches_collection->expects($this->once())
            ->method('all')
            ->willReturn($branches);

        $this->site->expects($this->once())
            ->method('getBranches')
            ->willReturn($branches_collection);

        $command = new ListCommand();
        $command->setSites($this->sites);
        $out = $command->listBranches('my-site');
        $this->assertEquals($branches_info, $out->getArrayCopy());
    }
}
