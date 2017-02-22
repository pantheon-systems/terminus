<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class OrganizationUserMembershipsTest
 * Testing class for Pantheon\Terminus\Collections\OrganizationUserMemberships
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class OrganizationUserMembershipsTest extends CollectionTestCase
{
    /**
     * @var OrganizationUserMemberships
     */
    protected $model;
    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new OrganizationUserMemberships(['organization' => $this->organization,]);
    }

    /**
     * Tests the OrganizationUserMemberships::create($email, $role) function
     */
    public function testCreate()
    {
        $params = ['user_email' => 'dev@example.com', 'role' => 'team_member',];
        $this->organization->id = '123';
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization->expects($this->once())
            ->method('getWorkflows')
            ->willReturn($workflows);
        $workflows->expects($this->once())
            ->method('create')
            ->with('add_organization_user_membership', compact('params'))
            ->willReturn($workflow);

        $out = $this->model->create($params['user_email'], $params['role']);
        $this->assertEquals($workflow, $out);
    }

    /**
     * Tests the OrganizationUserMemberships::getUrl() function, thereby testing its abstract parent
     */
    public function testGetUrl()
    {
        $this->organization->id = 'org id';
        $expected = "organizations/{$this->organization->id}/memberships/users";
        $out = $this->model->getUrl();
        $this->assertEquals($expected, $out);
    }
}
