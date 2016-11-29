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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->site = $this->getMockBuilder(Site::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->collection = new Branches(['site' => $this->site,]);
        $this->collection->setRequest($this->request);
    }

    /**
     * Tests Branches::fetch($options)
     */
    public function testFetch()
    {
    }
}
