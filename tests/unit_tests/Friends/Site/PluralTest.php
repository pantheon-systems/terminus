<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\UserSiteMembership;

/**
 * Class PluralTest
 * Testing class for Pantheon\Terminus\Friends\SitesTrait & Pantheon\Terminus\Friends\SitesInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class PluralTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserSiteMemberships
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

        $this->memberships = $this->getMockBuilder(UserSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new PluralDummyClass();
        $this->model->setSiteMemberships($this->memberships);
    }

    public function testGetSites()
    {
        $site_data = [
            ['id' => 'site a', 'name' => 'site name a',],
            ['id' => 'site b', 'name' => 'site name b',],
            ['id' => 'site c', 'name' => 'site name c',],
        ];
        $sites = [];
        $members = [];
        foreach ($site_data as $site) {
            $member_mock = $this->getMockBuilder(UserSiteMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $site_mock = $this->getMockBuilder(Site::class)
                ->setConstructorArgs([(object)$site,])
                ->getMock();
            $site_mock->id = $site['id'];
            $member_mock->expects($this->once())
                ->method('getSite')
                ->with()
                ->willReturn($site_mock);
            $members[] = $member_mock;
            $sites[$site_mock->id] = $site_mock;
        }

        $this->memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn($members);

        $this->assertEquals($sites, $this->model->getSites());
    }
}
