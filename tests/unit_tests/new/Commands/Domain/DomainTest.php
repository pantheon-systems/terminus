<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Collections\Hostnames;
use Terminus\Models\Hostname;

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
     * Test Suite Setup
     */
    protected function setUp()
    {
        parent::setUp();

        $this->environment->hostnames = $this->hostnames = $this->getMockBuilder(Hostnames::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hostname = $this->getMockBuilder(Hostname::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
