<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUpstream;

/**
 * Class OrganizationUpstreamsTest
 * Testing class for Pantheon\Terminus\Collections\OrganizationUpstreams
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class OrganizationUpstreamsTest extends CollectionTestCase
{
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
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->id = 'org id';

        $this->collection = new OrganizationUpstreams(['organization' => $this->organization,]);
        $this->collection->setContainer($this->container);
    }

    /**
     * Tests the Upstreams::filterByName(string) function
     */
    public function testFilterByName()
    {
        $data = [
            'a' => (object)['id' => 'a', 'label' => 'WordPress',],
            'b' => (object)['id' => 'b', 'label' => 'Drupal 7',],
            'c' => (object)['id' => 'c', 'label' => 'Drupal8',],
        ];
        $i = 0;
        foreach ($data as $model_data) {
            $options = ['collection' => $this->collection, 'id' => $model_data->id,];
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(OrganizationUpstream::class, [$model_data, $options,])
                ->willReturn(new OrganizationUpstream($model_data, $options));
        }
        foreach ($data as $model_data) {
            $this->collection->add($model_data);
        }
        $unfiltered = $this->collection->all();
        $drupal_only = $this->collection->filterByName('Drupal')->all();

        $this->assertEquals(count($data), count($unfiltered));
        $this->assertEquals(2, count($drupal_only));

        array_shift($unfiltered);
        $this->assertEquals($unfiltered, $drupal_only);
    }
}
