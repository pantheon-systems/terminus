<?php

namespace Pantheon\Terminus\UnitTests\Collection;

use League\Container\Container;
use Pantheon\Terminus\Request\Request;

class CollectionTestCase extends \PHPUnit_Framework_TestCase
{
    protected $request;
    protected $container;

    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
