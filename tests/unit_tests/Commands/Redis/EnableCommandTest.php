<?php

namespace Pantheon\Terminus\UnitTests\Commands\Redis;

use Pantheon\Terminus\Commands\Redis\EnableCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Redis;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class EnableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Redis\EnableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Redis
 */
class EnableCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * Tests the redis:enable command
     */
    public function testEnable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->redis = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redis->expects($this->once())
            ->method('enable');
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);
        $this->site->method('getRedis')->willReturn($this->redis);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Redis enabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command = new EnableCommand();
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
        $this->command->enable('mysite');
    }
}
