<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Upstream\ListCommand;

class ListCommandTest extends UpstreamCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSession($this->session);
    }

    /**
     * Tests the upstream:list command
     */
    public function testListUpstreams()
    {
        $this->upstreams->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->upstream,]);

        $out = $this->command->listUpstreams();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->data,], $out->getArrayCopy());
    }
}
