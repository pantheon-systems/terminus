<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Terminus\Collections\Tags;
use Terminus\Models\Organization;
use Terminus\Models\OrganizationSiteMembership;
use Terminus\Models\Site;
use Terminus\Models\Tag;

/**
 * Testing class for Terminus\Models\Tag
 */
class TagTest extends ModelTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->collection = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->org_site_membership->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->org_site_membership->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Tag(null, ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }


    /**
     * Tests Tag::delete()
     */
    public function testDelete()
    {
        $this->model->id = 'tag_id';
        $this->model->org_site_membership->site->id = 'site_uuid';
        $this->model->org_site_membership->organization->id = 'org_uuid';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('organizations/org_uuid/tags/tag_id/sites?entity=site_uuid'),
                $this->equalTo(['method' => 'delete',])
            );
        $this->model->delete();
    }
}
