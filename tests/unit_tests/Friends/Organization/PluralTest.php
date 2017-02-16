<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Organization;

/**
 * Class PluralTest
 * Testing class for Pantheon\Terminus\Friends\OrganizationsTrait & Pantheon\Terminus\Friends\OrganizationsInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class PluralTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteOrganizationMemberships
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

        $this->memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new PluralDummyClass();
        $this->model->setOrganizationMemberships($this->memberships);
    }

    public function testGetOrganizations()
    {
        $organization_data = [
            ['id' => 'organization a', 'name' => 'organization name a',],
            ['id' => 'organization b', 'name' => 'organization name b',],
            ['id' => 'organization c', 'name' => 'organization name c',],
        ];
        $organizations = [];
        $members = [];
        foreach ($organization_data as $organization) {
            $member_mock = $this->getMockBuilder(SiteOrganizationMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $organization_mock = $this->getMockBuilder(Organization::class)
                ->setConstructorArgs([(object)$organization,])
                ->getMock();
            $organization_mock->id = $organization['id'];
            $member_mock->expects($this->once())
                ->method('getOrganization')
                ->with()
                ->willReturn($organization_mock);
            $members[] = $member_mock;
            $organizations[$organization_mock->id] = $organization_mock;
        }

        $this->memberships->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn($members);

        $this->assertEquals($organizations, $this->model->getOrganizations());
    }
}
