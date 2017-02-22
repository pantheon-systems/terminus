<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\UserOwnedCollection;
use Pantheon\Terminus\Models\User;

/**
 * Class UserOwnedCollectionTest
 * Testing class for Pantheon\Terminus\Collections\UserOwnedCollection
 * @package Pantheon\Terminus\UnitTests\Collections
 */
abstract class UserOwnedCollectionTest extends CollectionTestCase
{
    /**
     * @var string
     */
    protected $class = UserOwnedCollection::class;
    /**
     * @var UserOwnedCollection
     */
    protected $collection;
    /**
     * @var null|string
     */
    protected $url = null;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        
        $user = new User((object)['id' => 'USERID']);
        $this->collection = new $this->class(['user' => $user]);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($this->container);
    }

    /**
     * Tests the UserOwnedCollection::getUrl() function
     */
    public function testGetURL()
    {
        $this->assertEquals($this->url, $this->collection->getUrl());
    }
}
