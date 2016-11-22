<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Upstream;

abstract class UpdatesCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->method('getUpstream')->willReturn($this->upstream);
    }
}
