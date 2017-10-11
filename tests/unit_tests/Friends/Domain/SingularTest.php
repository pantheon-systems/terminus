<?php

namespace Pantheon\Terminus\UnitTests\Friends\Domain;

use Pantheon\Terminus\Models\Domain;

/**
 * Class SingularTest
 * Testing class for Pantheon\Terminus\Friends\DomainTrait & Pantheon\Terminus\Friends\DomainInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Domain
 */
class SingularTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Domain
     */
    protected $domain;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->domain = $this->getMockBuilder(Domain::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests DomainTrait::getDomain() and DomainTrait::__construct(array)
     */
    public function testGetDomain()
    {
        $model = new SingularDummyClass(['domain' => $this->domain,]);
        $this->assertEquals($this->domain, $model->getDomain());
    }

    /**
     * Tests DomainTrait::setDomain()
     */
    public function testSetDomain()
    {
        $model = new SingularDummyClass();
        $model->setDomain($this->domain);
        $this->assertEquals($this->domain, $model->getDomain());
    }
}
