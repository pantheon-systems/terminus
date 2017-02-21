<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Models\User;

/**
 * Class JoinTest
 * Testing class for Pantheon\Terminus\Friends\UserJoinTrait & Pantheon\Terminus\Friends\UserJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class JoinTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JoinDummyClass
     */
    protected $model;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = new JoinDummyClass();
        $this->model->id = 'model id';
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests UserJoinTrait::*()
     */
    public function testAll()
    {
        $user_references = ['model', 'thing', 'name',];
        $expected = array_merge([$this->model->id,], $user_references);

        $this->user->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($user_references);

        $this->model->setUser($this->user);
        $this->assertEquals($expected, $this->model->getReferences());
        $this->assertEquals($this->user, $this->model->getUser());
    }
}
