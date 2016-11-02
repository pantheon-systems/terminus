<?php


namespace Pantheon\Terminus\UnitTests\Commands\Redis;

use Pantheon\Terminus\Commands\Redis\ClearCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Redis;

class ClearCommandTest extends CommandTestCase
{
    public function testClearRedis()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('connectionInfo')
            ->willReturn(['redis_host' => 'xyz']);

        $this->site->redis = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->redis->expects($this->once())
            ->method('clear')
            ->with($this->environment)
            ->willReturn($workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Clearing Redis on {env}.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $command = new ClearCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->clearRedis('mysite.dev');
    }

    public function testClearRedisNotEnabled()
    {
        $this->environment->expects($this->once())
            ->method('connectionInfo')
            ->willReturn([]);
        $this->site->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn('mysite');

        $this->setExpectedException(TerminusException::class, 'Redis cache is not enabled for mysite.');

        $command = new ClearCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->clearRedis('mysite.dev');
    }
}
