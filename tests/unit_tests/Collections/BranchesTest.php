<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Terminus\Models\Site;
use Terminus\Collections\Branches;

/**
 * Testing class for Terminus\Collections\Branches
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
