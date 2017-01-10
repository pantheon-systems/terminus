<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Branches;

/**
 * Class BranchesTest
 * Testing class for Pantheon\Terminus\Collections\Branches
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class BranchesTest extends CollectionTestCase
{
    /**
     * @var array
     */
    protected $collection_data;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->site = $this->getMockBuilder(Site::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->collection_data = ['a' => 'sha1', 'b' => 'sha2', 'c' => 'sha3',];
        $this->collection = $this->getMockBuilder(Branches::class)
            ->setMethods(['getCollectionData', 'add',])
            ->enableOriginalConstructor()
            ->setConstructorArgs([['site' => $this->site,],])
            ->getMock();
    }

    /**
     * Tests Branches::fetch($options) when data is not provided via the options
     */
    public function testFetch()
    {
        $this->collection->expects($this->once())
            ->method('getCollectionData')
            ->willReturn($this->collection_data);

        $out = $this->collection->fetch();
        $this->assertEquals($out, $this->collection);
    }

    /**
     * Tests Branches::fetch($options) when data is provided via the options
     */
    public function testFetchFromOptions()
    {
        $this->collection->expects($this->never())
            ->method('getCollectionData');
        $this->expectAdditions();

        $out = $this->collection->fetch(['data' => $this->collection_data,]);
        $this->assertEquals($out, $this->collection);
    }

    protected function expectAdditions()
    {
        $counter = 0;
        foreach ($this->collection_data as $id => $sha) {
            $this->collection->expects($this->at($counter++))
                ->method('add')
                ->with($this->equalTo((object)['id' => $id, 'sha' => $sha,]));
        }
    }
}
