<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Models\Organization;

/**
 * Class JoinTest
 * Testing class for Pantheon\Terminus\Friends\OrganizationJoinTrait & Pantheon\Terminus\Friends\OrganizationJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class JoinTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JoinDummyClass
     */
    protected $model;
    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = new JoinDummyClass();
        $this->model->id = 'model id';
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests OrganizationJoinTrait::*()
     */
    public function testAll()
    {
        $organization_references = ['model', 'thing', 'name',];
        $expected = array_merge([$this->model->id,], $organization_references);

        $this->organization->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($organization_references);

        $this->model->setOrganization($this->organization);
        $this->assertEquals($expected, $this->model->getReferences());
        $this->assertEquals($this->organization, $this->model->getOrganization());
    }
}
