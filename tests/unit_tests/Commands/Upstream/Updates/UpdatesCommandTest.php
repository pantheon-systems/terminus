<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Models\UpstreamUpdate;
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

        $this->upstream_update = $this->getMockBuilder(UpstreamUpdate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getUpstreamUpdate')->willReturn($this->upstream_update);
    }
}
