<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Upstream\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream
 */
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
            ->method('serialize')
            ->with()
            ->willReturn([$this->data['id'] => $this->data]);

        $out = $this->command->listUpstreams();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$this->data['id'] => $this->data], $out->getArrayCopy());
    }
}
