<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Tags;

/**
 * Class TagsTest
 * Testing class for Pantheon\Terminus\Collections\Tags
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class TagsTest extends CollectionTestCase
{
    /**
     * @var Tags
     */
    protected $collection;
    /**
     * @var OrganizationSiteMembership
     */
    protected $org_site_membership;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $container = $this->createMock(Container::class);
        $this->org_site_membership = $this->createMock(OrganizationSiteMembership::class);
        $this->organization = $this->createMock(Organization::class);
        $this->organization->id = 'org uuid';
        $this->site = $this->createMock(Site::class);
        $this->site->id = 'site_uuid';

        $this->org_site_membership->method('getOrganization')->willReturn($this->organization);
        $this->org_site_membership->method('getSite')->willReturn($this->site);

        $this->collection = new Tags(['org_site_membership' => $this->org_site_membership,]);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($container);
    }

    /**
     * Tests Tags::create($tag)
     */
    public function testCreate()
    {
        $tag_id = 'tag_id';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("organizations/{$this->organization->id}/tags"),
                $this->equalTo(
                    [
                        'form_params' => [$tag_id => ['sites' => ['site_uuid',],],],
                        'method' => 'put',
                    ]
                )
            );
        $this->collection->create($tag_id);
    }

    /**
     * Tests Tags::fetch($options)
     */
    public function testFetch()
    {
        $data = ['tag1',];
        $this->collection->fetch($data);
        $this->assertEquals($this->collection->ids(), $data);
    }

    /**
     * Tests Tags::has($id)
     */
    public function testHas()
    {
        $data = ['tag1',];
        $this->collection->fetch($data);
        $this->assertTrue($this->collection->has($data[0]));
        $this->assertFalse($this->collection->has('invalid'));
    }

    /**
     * Tests the Tags::getMembership() function
     */
    public function testGetMembership()
    {
        $this->assertEquals($this->org_site_membership, $this->collection->getMembership());
    }
}
