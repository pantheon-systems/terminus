<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Models\Domain;

/**
 * Class DomainTest
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
abstract class DomainTest extends CommandTestCase
{
    /**
     * @var Domain
     */
    protected $domain;
    /**
     * @var Domains
     */
    protected $domains;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->domains = $this->getMockBuilder(Domains::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getDomains')->willReturn($this->domains);

        $this->domain = $this->getMockBuilder(Domain::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
