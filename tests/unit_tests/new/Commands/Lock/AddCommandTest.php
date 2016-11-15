<?php


namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Pantheon\Terminus\Commands\Lock\AddCommand;
use Pantheon\Terminus\Models\Workflow;

class AddCommandTest extends LockCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    /**
     * Tests the lock:add command
     */
    public function testAdd()
    {
        $username = 'username';
        $password = 'password';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site_name = 'site_name';
        $this->env->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('add')
            ->with($this->equalTo(['username' => $username, 'password' => $password,]))
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
                $this->equalTo('{site}.{env} has been locked.'),
                $this->equalTo(['site' => $site_name, 'env' => $this->env->id,])
            );

        $out = $this->command->add("$site_name.{$this->env->id}", $username, $password);
        $this->assertNull($out);
    }
}
