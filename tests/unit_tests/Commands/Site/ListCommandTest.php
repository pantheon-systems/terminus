<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Commands\Site\ListCommand;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ListCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @var Session
     */
    private $session;
    /**
     * @var User
     */
    private $user;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->id = 'userID';

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the site:list command with no filters and all membership types
     */
    public function testListAllSites()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
        ];

        $this->site->memberships = ['org_id: org_url'];
        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['abc' => $dummy_info, 'def' => $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['abc' => $dummy_info, 'def' => $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Exercises site:list with no filters and team membership type
     */
    public function testListTeamSitesOnly()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'user_id: Team',
        ];

        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => true,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' =>  $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => true, 'owner' => null, 'org' => 'all', 'name' => null, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command using no filters and belonging to an org
     */
    public function testListOrgSitesOnly()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
        ];

        $user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org->id = 'orgID';

        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($user_org_memberships);
        $user_org_memberships->expects($this->once())
            ->method('get')
            ->with($org->id)
            ->willReturn($user_org_membership);
        $user_org_membership->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($org);
        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => $org->id, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' =>  $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => null, 'org' => $org->id, 'name' => null, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when filtering for either membership type
     */
    public function testListByNameRegex()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
        ];
        $regex = '(.*)';

        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('filterByName')
            ->with($this->equalTo($regex))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' =>  $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => null, 'org' => 'all', 'name' => $regex, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when either membership type owned by a user of a given ID
     */
    public function testListByOwner()
    {
        $user_id = 'user_id';
        $dummy_info = [
          'name' => 'my-site',
          'id' => 'site_id',
          'service_level' => 'pro',
          'framework' => 'cms',
          'owner' => $user_id,
          'created' => '1984-07-28 16:40',
          'memberships' => 'org_id: org_url',
        ];

        $this->site->memberships = ['org_id: org_url'];
        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->once())
            ->method('filterByOwner')
            ->with($this->equalTo($user_id))
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' => $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => $user_id, 'org' => 'all', 'name' => null, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when either membership type owned by a user of a given ID
     */
    public function testListByUpstream()
    {
        $product_id = '8a129104-9d37-4082-aaf8-e6f31154644e';
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
            'product_id' => $product_id,
        ];

        $this->site->memberships = ['org_id: org_url'];
        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('filterByUpstream')
            ->with($product_id)
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' => $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => null, 'org' => 'all', 'name' => null, 'upstream' => $product_id,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when either membership type owned by a user is the logged-in user
     */
    public function testListMyOwn()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'service_level' => 'pro',
            'framework' => 'cms',
            'owner' => $this->user->id,
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
        ];

        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->once())
            ->method('filterByOwner')
            ->with($this->equalTo($this->user->id))
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' =>  $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => 'me', 'org' => 'all', 'name' => null, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when filtering for plan name
     */
    public function testListByPlanName()
    {
        $dummy_info = [
            'name' => 'my-site',
            'id' => 'site_id',
            'plan_name' => 'Basic',
            'framework' => 'cms',
            'owner' => 'user_id',
            'created' => '1984-07-28 16:40',
            'memberships' => 'org_id: org_url',
        ];
        $plan_name = 'Sandbox';

        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('filterByPlanName')
            ->with($this->equalTo($plan_name))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['a' => $dummy_info, 'b' =>  $dummy_info,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->index(['team' => false, 'owner' => null, 'org' => 'all', 'name' => null, 'plan' => $plan_name, 'upstream' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals(['a' => $dummy_info, 'b' =>  $dummy_info,], $out->getArrayCopy());
    }

    /**
     * Tests the site:list command when the user has no sites
     */
    public function testListNoSites()
    {
        $this->sites->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(['org_id' => null, 'team_only' => false,]))
            ->willReturn($this->sites);
        $this->sites->expects($this->never())
            ->method('filterByName');
        $this->sites->expects($this->never())
            ->method('filterByOwner');
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo('You have no sites.')
            );

        $out = $this->command->index();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([], $out->getArrayCopy());
    }
}
