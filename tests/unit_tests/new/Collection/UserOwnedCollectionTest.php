<?php


namespace Pantheon\Terminus\UnitTests\Collection;

use Pantheon\Terminus\Collections\UserOwnedCollection;
use Pantheon\Terminus\Models\User;

class UserOwnedCollectionTest extends CollectionTestCase
{
    protected $url = null;
    protected $class = 'Pantheon\Terminus\Collections\UserOwnedCollection';

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
