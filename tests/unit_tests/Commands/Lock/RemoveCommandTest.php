<?php


namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Pantheon\Terminus\Commands\Lock\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;

class RemoveCommandTest extends LockCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    /**
     * Tests the lock:remove command
     */
    public function testRemove()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('remove')
            ->with()
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{site}.{env} has been unlocked.'),
                $this->equalTo(['site' => $site_name, 'env' => $this->environment->id,])
            );

        $out = $this->command->remove("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }
}
