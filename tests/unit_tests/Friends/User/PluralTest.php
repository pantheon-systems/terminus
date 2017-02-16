<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\User;

/**
 * Class PluralTest
 * Testing class for Pantheon\Terminus\Friends\UsersTrait & Pantheon\Terminus\Friends\UsersInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class PluralTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteUserMemberships
     */
    protected $memberships;
    /**
     * @var PluralDummyClass
     */
    protected $model;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new PluralDummyClass();
        $this->model->setUserMemberships($this->memberships);
    }

    public function testGetUsers()
    {
        $user_data = [
            ['id' => 'user a', 'name' => 'user name a',],
            ['id' => 'user b', 'name' => 'user name b',],
            ['id' => 'user c', 'name' => 'user name c',],
        ];
        $users = [];
        $members = [];
        foreach ($user_data as $user) {
            $member_mock = $this->getMockBuilder(SiteUserMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $user_mock = $this->getMockBuilder(User::class)
                ->setConstructorArgs([(object)$user,])
                ->getMock();
            $user_mock->id = $user['id'];
            $member_mock->expects($this->once())
                ->method('getUser')
                ->with()
                ->willReturn($user_mock);
            $members[] = $member_mock;
            $users[$user_mock->id] = $user_mock;
        }

        $this->memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn($members);

        $this->assertEquals($users, $this->model->getUsers());
    }
}
