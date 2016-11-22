<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Models\Tag;

/**
 * Testing class for Terminus\Collections\Tags
 */
class TagsTest extends CollectionTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->collection = new Tags(compact('org_site_membership'));

        $container = new Container();
        $container->add(Tag::class);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($container);
        $this->collection->org_site_membership = $org_site_membership;
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site_uuid';
        $this->collection->org_site_membership->method('getSite')->willReturn($this->site);
        $this->collection->org_site_membership->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests Tags::create($tag)
     */
    public function testCreate()
    {
        $tag_id = 'tag_id';
        $this->collection->org_site_membership->organization->id = 'org_uuid';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('organizations/org_uuid/tags'),
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
        $this->collection->fetch(compact('data'));
    }
}
