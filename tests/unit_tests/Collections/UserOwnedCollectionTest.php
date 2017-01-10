<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\UserOwnedCollection;
use Pantheon\Terminus\Models\User;

/**
 * Class UserOwnedCollectionTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UserOwnedCollectionTest extends CollectionTestCase
{
    /**
     * @var string
     */
    protected $class = UserOwnedCollection::class;
    /**
     * @var null|string
     */
    protected $url = null;

    protected $collection;

    public function setUp()
    {
        parent::setUp();
        
        $user = new User((object)['id' => 'USERID']);
        $this->collection = new $this->class(['user' => $user]);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($this->container);
    }

    public function testGetURL()
    {
        $this->assertEquals($this->url, $this->collection->getUrl());
    }
}
