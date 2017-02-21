<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\UserSiteMembership;
use Pantheon\Terminus\Models\User;

/**
 * Class UserSiteMembershipTest
 * Testing class for Pantheon\Terminus\Models\UserSiteMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class UserSiteMembershipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserSiteMemberships
     */
    protected $collection;
    /**
     * @var array
     */
    protected $site_data;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(UserSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_data = [
            'id' => 'site id',
            'name' => 'site name',
            'label' => 'Site Label',
        ];

        $this->model = new UserSiteMembership(
            (object)['site' => $this->site_data, 'id' => 'model id',],
            ['collection' => (object)$this->collection,]
        );
    }

    /**
     * Tests the UserSiteMemberships::__toString() function
     */
    public function testToString()
    {
        $user = $this->expectGetUser();
        $out = (string)$this->model;
        $this->assertEquals("{$user->id}: Team", $out);
    }

    /**
     * Tests the UserSiteMemberships::getUser() function
     */
    public function testGetUser()
    {
        $user = $this->expectGetUser();
        $out = $this->model->getUser();
        $this->assertEquals($user, $out);
    }

    /**
     * Prepares the test case for the getSite() function.
     *
     * @return Site The site object getSite() will return
     */
    protected function expectGetSite()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Site::class),
                $this->equalTo([$this->site_data,])
            )
            ->willReturn($site);

        $this->model->setContainer($container);
        return $site;
    }

    /**
     * Prepares the test case for the getUser() function.
     *
     * @return User The user object getUser() will return
     */
    protected function expectGetUser()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = 'user ID';
        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        return $user;
    }
}
