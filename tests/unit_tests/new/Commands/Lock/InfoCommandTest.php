<?php

namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Lock\InfoCommand;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Lock\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Lock
 */
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
        $this->environment->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->info("$site_name.{$this->environment->id}");
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
