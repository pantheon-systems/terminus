<?php

namespace Pantheon\Terminus\UnitTests\Commands\Plan;

use Pantheon\Terminus\Commands\Plan\ListCommand;
use Pantheon\Terminus\Models\Plan;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Plans;

/**
 * Class ListCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Plan\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Plan
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * Tests the plan:list command
     */
    public function testListPlans()
    {
        $plans_info = [
            ['id' => 'master', 'sku' => 'xxx'],
            ['id' => 'another', 'sku' => 'yyy'],
        ];
        
        $plans_collection = $this->getMockBuilder(Plans::class)
            ->disableOriginalConstructor()
            ->getMock();
        $plans_collection->method('getCollectedClass')->willReturn(Plan::class);
        $plans_collection->expects($this->once())
            ->method('serialize')
            ->willReturn($plans_info);

        $this->site->expects($this->once())
            ->method('getPlans')
            ->willReturn($plans_collection);

        $command = new ListCommand();
        $command->setSites($this->sites);
        $out = $command->listPlans('my-site');
        $this->assertEquals($plans_info, $out->getArrayCopy());
    }
}
