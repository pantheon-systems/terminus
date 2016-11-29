<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class UpdatesCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream\Updates
 */
abstract class UpdatesCommandTest extends CommandTestCase
{
    /**
     * @var Upstream
     */
    protected $upstream;

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
