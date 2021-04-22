<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Tag;

/**
 * Class TagTest
 * Testing class for Pantheon\Terminus\Models\Tag
 * @package Pantheon\Terminus\UnitTests\Models
 */
class TagTest extends ModelTestCase
{
    /**
     * @var Tags
     */
    protected $collection;
    /**
     * @var Tag
     */
    protected $model;
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
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->method('getMembership')->willReturn($org_site_membership);
        $org_site_membership->method('getSite')->willReturn($this->site);
        $org_site_membership->method('getOrganization')->willReturn($this->organization);

        $this->model = new Tag(null, ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }


    /**
     * Tests Tag::delete()
     */
    public function testDelete()
    {
        $this->model->id = 'tag_id';
        $this->site->id = 'site_uuid';
        $this->organization->id = 'org_uuid';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('organizations/org_uuid/tags/tag_id/sites?entity=site_uuid'),
                $this->equalTo(['method' => 'delete',])
            );
        $this->model->delete();
    }
}
