<?php

namespace Pantheon\Terminus\UnitTests\Commands\Redis;

use Pantheon\Terminus\Commands\Redis\DisableCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Redis;

/**
 * Class DisableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Redis\DisableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Redis
 */
class DisableCommandTest extends CommandTestCase
{
    /**
     * Tests the redis:disable command
     */
    public function testDisable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->redis = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redis->expects($this->once())
            ->method('disable');
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);
        $this->site->method('getRedis')->willReturn($this->redis);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Redis disabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $command = new DisableCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->disable('mysite');
    }
}
