<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Session\Session;

class SiteTest extends TestCase
{
    private function createSite(
        array $attributes,
        array $userOrgIds,
        array $siteSupportingOrgIds = []
    ): Site {
        $site = $this->getMockBuilder(Site::class)
            ->setConstructorArgs([(object)array_merge(['id' => 'site-id'], $attributes)])
            ->onlyMethods(['getOrganizationMemberships'])
            ->getMock();

        $config = $this->createMock(TerminusConfig::class);
        $config->method('get')->willReturnMap([
            ['dashboard_protocol', null, 'https'],
            ['dashboard_host', null, 'dashboard.pantheon.io'],
        ]);
        $site->setConfig($config);

        $user = $this->createMock(User::class);
        $user->id = 'user-id';
        $userMemberships = $this->createMock(UserOrganizationMemberships::class);
        $userMemberships->method('all')->willReturn(array_map(
            fn ($orgId) => $this->createMembership(UserOrganizationMembership::class, $orgId),
            $userOrgIds
        ));
        $user->method('getOrganizationMemberships')->willReturn($userMemberships);

        $session = $this->createMock(Session::class);
        $session->method('getUser')->willReturn($user);
        $site->setSession($session);

        $siteMemberships = $this->createMock(SiteOrganizationMemberships::class);
        $siteMemberships->method('all')->willReturn(array_map(
            fn ($orgId) => $this->createMembership(SiteOrganizationMembership::class, $orgId),
            $siteSupportingOrgIds
        ));
        $site->method('getOrganizationMemberships')->willReturn($siteMemberships);

        return $site;
    }

    private function createMembership(string $class, string $orgId)
    {
        $org = $this->createMock(Organization::class);
        $org->id = $orgId;
        $membership = $this->createMock($class);
        $membership->method('getOrganization')->willReturn($org);
        return $membership;
    }

    public function testDashboardUrlUsesOwnerOrgWhenAccessible()
    {
        $site = $this->createSite(
            ['organization' => 'owner-org', 'framework' => 'wordpress'],
            ['owner-org', 'other-org']
        );

        $this->assertSame(
            'https://dashboard.pantheon.io/workspace/owner-org/cms-site/site-id',
            $site->dashboardUrl()
        );
    }

    public function testDashboardUrlFallsBackToSupportingOrgWhenOwnerOrgNotAccessible()
    {
        $site = $this->createSite(
            ['organization' => 'inaccessible-org', 'framework' => 'nodejs'],
            ['user-org'],
            ['other-supporting-org', 'user-org']
        );

        $this->assertSame(
            'https://dashboard.pantheon.io/workspace/user-org/node-site/site-id',
            $site->dashboardUrl()
        );
    }

    public function testDashboardUrlFallsBackToUserIdWhenNoAccessibleOrg()
    {
        $site = $this->createSite(
            ['organization' => null, 'framework' => 'wordpress'],
            []
        );

        $this->assertSame(
            'https://dashboard.pantheon.io/workspace/user-id/cms-site/site-id',
            $site->dashboardUrl()
        );
    }

    public function testDashboardUrlUsesExplicitOrgIdHintWithoutResolution()
    {
        $site = $this->createSite(
            ['organization' => null, 'framework' => 'wordpress'],
            []
        );

        $this->assertSame(
            'https://dashboard.pantheon.io/workspace/hinted-org/cms-site/site-id',
            $site->dashboardUrl('hinted-org')
        );
    }
}
