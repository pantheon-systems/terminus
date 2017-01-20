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
     * @var Container
     */
    protected $container;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var array
     */
    protected $site_data;
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

        $this->collection = $this->getMockBuilder(UserSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_data = [
            'id' => 'site id',
            'name' => 'site name',
            'label' => 'Site Label',
        ];
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->id = 'user ID';

        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);

        $this->model = new UserSiteMembership(
            (object)['site' => $this->site_data,],
            ['collection' => (object)$this->collection,]
        );
        $this->model->setContainer($this->container);
    }

    /**
     * Tests the UserSiteMemberships::__toString() function
     */
    public function testToString()
    {
        $out = (string)$this->model;
        $this->assertEquals("{$this->user->id}: Team", $out);
    }

    /**
     * Tests the UserSiteMemberships::getSite() function
     */
    public function testGetSite()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Site::class),
                $this->equalTo([$this->site_data,])
            )
            ->willReturn($this->site);
        $out = $this->model->getSite();
        $this->assertEquals($this->site, $out);
    }

    /**
     * Tests the UserSiteMemberships::getUser() function
     */
    public function testGetUser()
    {
        $out = $this->model->getUser();
        $this->assertEquals($this->user, $out);
    }
}
