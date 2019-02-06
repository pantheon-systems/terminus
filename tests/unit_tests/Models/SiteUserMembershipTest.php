<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteUserMembershipTest
 * Testing class for Pantheon\Terminus\Models\SiteUserMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteUserMembershipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteUserMemberships
     */
    protected $collection;
    /**
     * @var SiteUserMembership
     */
    protected $model;
    /**
     * @var string
     */
    protected $role;
    /**
     * @var array
     */
    protected $user_data;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->user_data = [
            'id' => 'abc',
            'firstname' => 'Daisy',
            'lastname' => 'Duck',
            'email' => 'daisy@duck.com',
        ];
        $this->role = 'role';
        $this->collection = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new SiteUserMembership(
            (object)['user' => $this->user_data, 'role' => $this->role,],
            ['collection' => $this->collection,]
        );
    }

    /**
     * Tests the SiteUserMembership::delete() function.
     */
    public function testDelete()
    {
        $user = $this->expectGetUser();
        $this->expectGetSite();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_site_user_membership',
                ['params' => ['user_id' => $user->id,],]
            )
            ->willReturn($this->workflow);

        $out = $this->model->delete();
        $this->assertEquals($this->workflow, $out);
    }

    /**
     * Tests the SiteUserMembership::getReferences() function.
     */
    public function testGetReferences()
    {
        $user = $this->expectGetUser();
        $user->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($this->user_data);

        $out = $this->model->getReferences();
        $this->assertEquals(array_merge([$this->model->id,], $this->user_data), $out);
    }

    /**
     * Tests the SiteUserMembership::isOwner() function when the user is not the site owner.
     */
    public function testIsNotOwner()
    {
        $site = $this->expectGetSite();
        $this->expectGetUser();

        $site->expects($this->once())
            ->method('get')
            ->with('owner')
            ->willReturn('nope');
        $this->assertFalse($this->model->isOwner());
    }

    /**
     * Tests the SiteUserMembership::isOwner() function when the user is the site owner.
     */
    public function testIsOwner()
    {
        $site = $this->expectGetSite();
        $user = $this->expectGetUser();

        $site->expects($this->once())
            ->method('get')
            ->with('owner')
            ->willReturn($user->id);
        $this->assertTrue($this->model->isOwner());
    }

    /**
     * Tests the SiteUserMembership::serialize() function.
     */
    public function testSerialize()
    {
        $site = $this->expectGetSite();
        $user = $this->expectGetUser();

        $site->expects($this->once())
            ->method('get')
            ->with('owner')
            ->willReturn($user->id);
        $user->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->user_data);
        $expected = array_merge($this->user_data, ['is_owner' => true, 'role' => $this->role,]);
        $out = $this->model->serialize();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests the SiteUserMembership::setRole() function.
     */
    public function testSetRole()
    {
        $user = $this->expectGetUser();
        $this->expectGetSite();

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'update_site_user_membership',
                ['params' => ['user_id' => $user->id, 'role' => $this->role,],]
            )
            ->willReturn($this->workflow);

        $out = $this->model->setRole($this->role);
        $this->assertEquals($this->workflow, $out);
    }

    /**
     * Prepares the test case for the getSite() function.
     *
     * @return Site The site object getSite() will return
     */
    protected function expectGetSite()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site ID';
        $site->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($site);
        return $site;
    }

    /**
     * Prepares the test case for the getUser() function.
     *
     * @return User The user object getUser() will return
     */
    protected function expectGetUser()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = $this->user_data['id'];

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($user);

        $this->model->setContainer($container);
        return $user;
    }
}
