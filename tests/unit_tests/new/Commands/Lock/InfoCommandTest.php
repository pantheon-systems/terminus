<?php


namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\Lock\InfoCommand;

class InfoCommandTest extends LockCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InfoCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    /**
     * Tests the lock:info command
     */
    public function testInfo()
    {
        $data = ['locked' => 'true', 'username' => 'username', 'password' => 'password',];

        $site_name = 'site_name';
        $this->env->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->info("$site_name.{$this->env->id}");
        $this->assertInstanceOf(AssociativeList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
