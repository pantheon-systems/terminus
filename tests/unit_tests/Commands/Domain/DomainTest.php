<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Hostnames;
use Pantheon\Terminus\Models\Hostname;

/**
 * Class DomainTest
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
abstract class DomainTest extends CommandTestCase
{
    /**
     * @var Hostname
     */
    protected $hostname;
    /**
     * @var Hostnames
     */
    protected $hostnames;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->hostnames = $this->getMockBuilder(Hostnames::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getHostnames')->willReturn($this->hostnames);

        $this->hostname = $this->getMockBuilder(Hostname::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
