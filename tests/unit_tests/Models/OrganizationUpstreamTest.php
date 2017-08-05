<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUpstream;

/**
 * Class OrganizationUpstreamTest
 * Tests the Pantheon\Terminus\Models\OrganizationUpstream class
 * @package Pantheon\Terminus\UnitTests\Models
 */
class OrganizationUpstreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationUpstreams
     */
    protected $collection;
    /**
     * @var array
     */
    protected $data;
    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->data = [
            'id' => 'upstream id',
            'label' => 'upstream label',
            'machine_name' => 'upstream machine name',
            'repository_url' => 'repository.url',
        ];
        $this->collection = $this->getMockBuilder(OrganizationUpstreams::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new OrganizationUpstream((object)$this->data, ['collection' => $this->collection,]);
    }

    /**
     * Tests the OrganizationUpstream::getReferences() function
     */
    public function testGetReferences()
    {
        $expected = $this->data;
        unset($expected['repository_url']);
        $this->assertEquals(array_values($expected), $this->model->getReferences());
    }

    /**
     * Tests the OrganizationUpstream::serialize() function
     */
    public function testSerialize()
    {
        $org_string = 'organization string';
        $expected = $this->data;
        $expected['organization'] = $org_string;

        $this->collection->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($this->organization);
        $this->organization->expects($this->once())
            ->method('getLabel')
            ->with()
            ->willReturn($org_string);

        $this->assertEquals($expected, $this->model->serialize());
    }
}
