<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org;

use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Commands\Org\ListCommand;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserOrganizationMemberships
     */
    protected $user_organization_memberships;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_organization_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user_organization_memberships
            ->method('getCollectedClass')
            ->willReturn(UserOrganizationMembership::class);
        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($this->user_organization_memberships);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:list command when the logged-in user is not a member of an organization
     */
    public function testOrgListEmpty()
    {
        $this->user_organization_memberships->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('warning'), $this->equalTo('You are not a member of any organizations.'));

        $out = $this->command->listOrgs();
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the multidev:list command when there are no multidev environments
     */
    public function testOrgListNotEmpty()
    {
        $data = [
          'id' => 'org_id',
          'name' => 'Organization Name',
        ];

        $this->logger->expects($this->never())
            ->method('log');
        $this->user_organization_memberships->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([$data, $data,]);

        $out = $this->command->listOrgs();
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);

        $this->assertEquals([$data, $data,], $out->getArrayCopy());
    }
}
