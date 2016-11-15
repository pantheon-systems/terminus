<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Terminus\Models\Organization;
use Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Terminus\Collections\Tags;

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

        $this->collection->setRequest($this->request);
        $this->collection->org_site_membership = $org_site_membership;
        $this->collection->org_site_membership->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->collection->org_site_membership->site->id = 'site_uuid';
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
