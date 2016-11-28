<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Upstream\InfoCommand;

/**
 * Class InfoCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream
 */
class InfoCommandTest extends UpstreamCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InfoCommand($this->getConfig());
        $this->command->setSession($this->session);
    }

    /**
     * Tests the upstream:info command
     */
    public function testInfo()
    {
        $upstream_id = 'upstream_id';
        $this->upstreams->expects($this->once())
            ->method('get')
            ->with($this->equalTo($upstream_id))
            ->willReturn($this->upstream);

        $out = $this->command->info($upstream_id);
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($this->data, $out->getArrayCopy());
    }
}
