<?php

namespace Pantheon\Terminus\UnitTests\Commands;


use Pantheon\Terminus\Session\Session;
use Psr\Log\NullLogger;
use Terminus\Collections\Sites;
use Terminus\Collections\SshKeys;
use Terminus\Models\Site;
use Terminus\Models\Upstream;
use Terminus\Models\User;

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
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->method('get')
            ->willReturn($this->site);

        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }
}
