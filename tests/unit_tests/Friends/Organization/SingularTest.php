<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Models\Organization;

/**
 * Class SingularTest
 * Testing class for Pantheon\Terminus\Friends\OrganizationTrait & Pantheon\Terminus\Friends\OrganizationInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class SingularTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SingularDummyClass
     */
    protected $collection;
    /**
     * @var SingularDummyClass
     */
    protected $model;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(SingularDummyClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new SingularDummyClass(null, ['collection' => $this->collection,]);
    }

    /**
     * Tests OrganizationTrait::getOrganization()
     */
    public function testGetOrganization()
    {
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($organization);

        $this->assertEquals($organization, $this->model->getOrganization());
    }

    /**
     * Tests OrganizationTrait::setOrganization()
     */
    public function testSetOrganization()
    {
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->never())->method('getOrganization');

        $this->model->setOrganization($organization);
        $this->assertEquals($organization, $this->model->getOrganization());
    }
}
