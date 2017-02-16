<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;

/**
 * Class SitesTest
 * Testing class for Pantheon\Terminus\Collections\Sites
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SitesTest extends CollectionTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;
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

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);

        $this->collection = new Sites();
        $this->collection->setSession($this->session);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($this->container);
    }

    public function testCreate()
    {
        $params = ['param1', 'param2'];

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('create_site'),
                $this->equalTo(compact('params'))
            )
            ->willReturn($this->workflow);

        $out = $this->collection->create($params);
        $this->assertEquals($out, $this->workflow);
    }

    public function testCreateForMigration()
    {
        $params = ['param1', 'param2'];

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('create_site_for_migration'),
                $this->equalTo(compact('params'))
            )
            ->willReturn($this->workflow);

        $out = $this->collection->createForMigration($params);
        $this->assertEquals($out, $this->workflow);
    }

    public function testFetchWhichReturnsNoSites()
    {
        $this->collection = $this->makeSitesFetchableWithNoSiteData($this->collection);
        $out = $this->collection->fetch();
        $this->assertEquals($this->collection, $out);
    }

    public function testFetch()
    {
        $this->collection = $this->makeSitesFetchable($this->collection);
        $out = $this->collection->fetch();
        $this->assertEquals($this->collection, $out);
    }

    public function testFetchOrgOnly()
    {
        $this->collection = $this->makeSitesFetchable($this->collection);
        $out = $this->collection->fetch(['org_id' => 'orgmembership',]);
        $this->assertEquals($this->collection, $out);
    }

    public function testFilterByName()
    {
        $this->collection = $this->makeSitesFetchable($this->collection);

        $this->site1->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn('site1');
        $this->site2->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn('site2');

        $this->assertEquals($this->collection->all(), [
            '11111111-1111-1111-1111-111111111111' => $this->site1,
            '22222222-2222-2222-2222-222222222222' => $this->site2,
        ]);
        $this->collection->filterByName('1');
        $this->assertEquals($this->collection->all(), ['11111111-1111-1111-1111-111111111111' => $this->site1,]);
    }

    public function testFilterByOwner()
    {
        $this->collection = $this->makeSitesFetchable($this->collection);

        $this->site1->expects($this->once())
            ->method('get')
            ->with($this->equalTo('owner'))
            ->willReturn('person1');
        $this->site2->expects($this->once())
            ->method('get')
            ->with($this->equalTo('owner'))
            ->willReturn('person2');

        $this->assertEquals($this->collection->all(), [
            '11111111-1111-1111-1111-111111111111' => $this->site1,
            '22222222-2222-2222-2222-222222222222' => $this->site2,
        ]);
        $this->collection->filterByOwner('person2');
        $this->assertEquals($this->collection->all(), ['22222222-2222-2222-2222-222222222222' => $this->site2,]);
    }

    public function testFilterByTag()
    {
        $this->collection = $this->makeSitesFetchable($this->collection);
        $tag = 'this tag';

        $this->site1->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site2->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site1->tags->expects($this->once())
            ->method('has')
            ->with($this->equalTo($tag))
            ->willReturn(true);
        $this->site2->tags->expects($this->once())
            ->method('has')
            ->with($this->equalTo($tag))
            ->willReturn(false);

        $this->assertEquals($this->collection->all(), [
            '11111111-1111-1111-1111-111111111111' => $this->site1,
            '22222222-2222-2222-2222-222222222222' => $this->site2,
        ]);
        $this->collection->filterByTag($tag);
        $this->assertEquals($this->collection->all(), ['11111111-1111-1111-1111-111111111111' => $this->site1,]);
    }

    /**
     * Tests the Sites::get(string) function when getting a site by UUID
     */
    public function testGetByUUID()
    {
        $uuid = '11111111-1111-1111-1111-111111111111';
        $args = [(object)['id' => $uuid,], ['id' => $uuid, 'collection' => $this->collection,],];

        $site = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs($args)
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Site::class),
                $this->equalTo($args)
            )
            ->willReturn($site);
        $site->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($site);

        $out = $this->collection->get($uuid);
        $this->assertEquals($out, $site);
    }

    /**
     * Tests the Sites::get(string) function when getting a site by name
     */
    public function testGetByName()
    {
        $uuid = '11111111-1111-1111-1111-111111111111';
        $site_name = 'site-name';
        $args = [(object)['id' => $uuid,], ['id' => $uuid, 'collection' => $this->collection,],];

        $site = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs($args)
            ->getMock();

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("site-names/$site_name"),
                $this->equalTo(['method' => 'get',])
            )
            ->willReturn(['data' => (object)['id' => $uuid,],]);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Site::class),
                $this->equalTo($args)
            )
            ->willReturn($site);
        $site->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($site);

        $out = $this->collection->get($site_name);
        $this->assertEquals($out, $site);
    }

    /**
     * Tests the Sites::get(string) function when getting a site when the sites have been fetched already
     */
    public function testGetFetched()
    {
        $uuid = '11111111-1111-1111-1111-111111111111';
        $this->collection = $this->makeSitesFetchable($this->collection);
        $this->collection->fetch();

        $out = $this->collection->get($uuid);
        $this->assertEquals($out, $this->site1);
    }

    /**
     * Tests the Sites::get(string) function when getting a site by UUID which doesn't exist or belong to the user
     */
    public function testGetWhenDNE()
    {
        $uuid = '11111111-1111-1111-1111-111111111111';
        $args = [(object)['id' => $uuid,], ['id' => $uuid, 'collection' => $this->collection,],];

        $site = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs($args)
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Site::class),
                $this->equalTo($args)
            )
            ->willReturn($site);
        $site->expects($this->once())
            ->method('fetch')
            ->with()
            ->will($this->throwException(new \Exception()));

        $this->setExpectedException(
            TerminusException::class,
            "Could not locate a site your user may access identified by $uuid."
        );

        $out = $this->collection->get($uuid);
        $this->assertNull($out);
    }

    /**
     * Tests the Sites::nameIsTaken(string) function when the name is not taken
     */
    public function testNameIsNotTaken()
    {
        $site_name = 'site name';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("site-names/$site_name"),
                $this->equalTo(['method' => 'get',])
            )
            ->will($this->throwException(new \Exception('404 Not Found')));

        $out = $this->collection->nameIsTaken($site_name);
        $this->assertFalse($out);
    }

    /**
     * Tests the Sites::nameIsTaken(string) function when the name is taken
     */
    public function testNameIsTaken()
    {
        $site_name = 'site name';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("site-names/$site_name"),
                $this->equalTo(['method' => 'get',])
            )
            ->will($this->throwException(new \Exception('403 Forbidden')));

        $out = $this->collection->nameIsTaken($site_name);
        $this->assertTrue($out);
    }

    /**
     * Tests the Sites::nameIsTaken(string) function when the name is taken and the site belongs to the user
     */
    public function testNameIsTakenAndMine()
    {
        $site_name = 'site-name';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("site-names/$site_name"),
                $this->equalTo(['method' => 'get',])
            )
            ->willReturn(['data' => (object)['id' => 'site_id',]]);

        $out = $this->collection->nameIsTaken($site_name);
        $this->assertTrue($out);
    }

    /**
     * @param Sites $sites Sites object to make fetchable
     * @return Sites
     */
    protected function makeSitesFetchable(Sites $sites)
    {
        $id1 = '11111111-1111-1111-1111-111111111111';
        $name1 = 'site1';
        $this->site1 = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                (object)['id' => $id1, 'name' => $name1, 'owner' => 'person1',],
                ['collection' => $sites,]
            ])
            ->getMock();
        $site_org_membership = $this->getMockBuilder(SiteOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site1->memberships = [$site_org_membership, $site_org_membership,];
        $this->site1->method('getReferences')->with()->willReturn([$id1, $name1,]);
        $id2 = '22222222-2222-2222-2222-222222222222';
        $name2 = 'site2';
        $this->site2 = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                (object)['id' => $id2, 'name' => $name2, 'owner' => 'person2',],
                ['collection' => $sites,]
            ])
            ->getMock();
        $site_user_membership = $this->getMockBuilder(SiteUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site2->memberships = [$site_user_membership,];
        $this->site2->method('getReferences')->with()->willReturn([$id2, $name2,]);
        $org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org_membership = $this->getMockBuilder(SiteOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->expects($this->any())
            ->method('getSites')
            ->with()
            ->willReturn([$this->site1, $this->site2,]);
        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($org_memberships);
        $org_memberships->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($org_memberships);
        $org_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$org_membership,]);
        $org_membership->expects($this->any())
            ->method('get')
            ->with($this->equalTo('role'))
            ->willReturn('admin');
        $org_membership->expects($this->any())
            ->method('getOrganization')
            ->with()
            ->willReturn($org);
        $org->expects($this->any())
            ->method('getSites')
            ->with()
            ->willReturn([$this->site1,]);

        return $sites;
    }

    /**
     * @param Sites $sites Sites object to make fetchable
     * @return Sites
     */
    protected function makeSitesFetchableWithNoSiteData(Sites $sites)
    {
        $id1 = '11111111-1111-1111-1111-111111111111';
        $name1 = 'site1';
        $this->site1 = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                (object)['id' => $id1, 'name' => $name1, 'owner' => 'person1',],
                ['collection' => $sites,]
            ])
            ->getMock();
        $this->site1->memberships = ['orgmembership', 'usermembership',];
        $this->site1->method('getReferences')->with()->willReturn([$id1, $name1,]);
        $id2 = '22222222-2222-2222-2222-222222222222';
        $name2 = 'site2';
        $this->site2 = $this->getMockBuilder(Site::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                (object)['id' => $id2, 'name' => $name2, 'owner' => 'person2',],
                ['collection' => $sites,]
            ])
            ->getMock();
        $this->site2->memberships = ['usermembership',];
        $this->site2->method('getReferences')->with()->willReturn([$id2, $name2,]);
        $org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->expects($this->any())
            ->method('getSites')
            ->with()
            ->willReturn([$this->site1, $this->site2,]);
        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($org_memberships);
        $org_memberships->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($org_memberships);
        $org_memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn(null);

        return $sites;
    }
}
