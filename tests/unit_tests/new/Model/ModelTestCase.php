<?php


namespace Pantheon\Terminus\UnitTests\Model;

use Pantheon\Terminus\Request\Request;

class ModelTestCase extends \PHPUnit_Framework_TestCase
{
    protected $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
