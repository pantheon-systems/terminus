<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Models\User;

/**
 * Class SingularTest
 * Testing class for Pantheon\Terminus\Friends\UserTrait & Pantheon\Terminus\Friends\UserInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class SingularTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SingularDummyClass
     */
    protected $collection;
    /**
     * @var SingularDummyClass
     */
    protected $model;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(SingularDummyClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new SingularDummyClass(null, ['collection' => $this->collection,]);
    }

    /**
     * Tests UserTrait::getUser()
     */
    public function testGetUser()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);

        $this->assertEquals($user, $this->model->getUser());
    }

    /**
     * Tests UserTrait::setUser()
     */
    public function testSetUser()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->never())->method('getUser');

        $this->model->setUser($user);
        $this->assertEquals($user, $this->model->getUser());
    }
}
