<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Models\UpstreamStatus;
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
    protected $upstream_status;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->upstream_status = $this->getMockBuilder(UpstreamStatus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getUpstreamStatus')->willReturn($this->upstream_status);
    }
}
