<?php

namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Pantheon\Terminus\Commands\Lock\EnableCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class EnableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Lock\EnableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Lock
 */
class EnableCommandTest extends LockCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new EnableCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    /**
     * Tests the lock:enable command
     */
    public function testEnable()
    {
        $username = 'username';
        $password = 'password';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('enable')
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
                $this->equalTo(['site' => $site_name, 'env' => $this->environment->id,])
            );

        $out = $this->command->enable("$site_name.{$this->environment->id}", $username, $password);
        $this->assertNull($out);
    }
}
