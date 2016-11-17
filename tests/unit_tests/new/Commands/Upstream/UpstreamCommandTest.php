<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Terminus\Models\Upstream;

abstract class UpstreamCommandTest extends CommandTestCase
{
    protected $upstreams;
    protected $logger;
    protected $command;
    protected $site;
    protected $sites;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
