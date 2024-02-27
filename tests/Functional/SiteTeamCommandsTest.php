<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SiteTeamCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SiteTeamCommandsTest extends TerminusTestBase
{
    private const TEST_SITE_TAG = 'test-site-list';

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\ListCommand
     *
     * @group site-team
     * @group short
     */
    public function testSiteTeamListCommand(): void
    {
        $team = $this->terminusJsonResponse(sprintf('site:team:list %s', $this->getSiteName()));
        $this->assertIsArray($team);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\AddCommand
     * @covers \Pantheon\Terminus\Commands\Site\Team\RemoveCommand
     *
     * @group site-team
     * @group short
     */
    public function testSiteTeamAddRemoveCommands(): void
    {
        $this->removeTestUser();
        $this->addTestUser();

        $team = $this->terminusJsonResponse(sprintf('site:team:list %s', $this->getSiteName()));
        $this->assertIsArray($team);
        $this->assertNotEmpty($team);
        $emails = array_column($team, 'email');
        $this->assertContains($this->getUserEmail(), $emails);

        $this->terminus(sprintf('site:team:remove %s %s', $this->getSiteName(), $this->getUserEmail()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\ListCommand
     *
     * @group site-team
     * @group site
     * @group tag
     * @group long
     *
     * @throws \Exception
     */
    public function testSiteTeamSiteList(): void
    {
        $siteTags = $this->terminusJsonResponse(sprintf('tag:list %s %s', $this->getSiteName(), $this->getOrg()));
        if (!in_array(self::TEST_SITE_TAG, $siteTags)) {
            $this->terminus(sprintf('tag:add %s %s %s', $this->getSiteName(), $this->getOrg(), self::TEST_SITE_TAG));
        }

        $this->removeTestUser();
        $this->assertSiteListContainsTaggedSite();

        $this->addTestUser();
        $this->assertSiteListContainsTaggedSite();
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\RoleCommand
     *
     * @group site-team
     * @group short
     */
    public function testSiteTeamRoleCommand(): void
    {
        self::callTerminus(
            sprintf('site:team:add %s %s', $this->getSiteName(), $this->getUserEmail())
        );

        [$stdout, $exitCode, $stderr] = self::callTerminus(
            sprintf('site:team:role %s %s team_member', $this->getSiteName(), $this->getUserEmail())
        );

        printf("stdout: %s\n", $stdout);
        printf("stderr: %s\n", $stderr);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Removes test site team member  if exists.
     */
    private function removeTestUser(): void
    {
        $team = $this->terminusJsonResponse(sprintf('site:team:list %s', $this->getSiteName()));
        $this->assertIsArray($team);
        if (0 === count($team)) {
            return;
        }

        $emails = array_column($team, 'email');
        if (!in_array($this->getUserEmail(), $emails)) {
            return;
        }

        $this->terminus(sprintf('site:team:remove %s %s', $this->getSiteName(), $this->getUserEmail()));
    }

    /**
     * Adds test site team member.
     */
    private function addTestUser(): void
    {
        $this->terminus(sprintf('site:team:add %s %s', $this->getSiteName(), $this->getUserEmail()));
    }

    /**
     * Asserts tagged site is present in the list of sites.
     *
     * @throws \Exception
     */
    private function assertSiteListContainsTaggedSite(): void
    {
        $siteList = $this->terminusJsonResponse(
            sprintf("site:list --filter='tags*=%s'", self::TEST_SITE_TAG)
        );
        $this->assertIsArray($siteList);
        $this->assertCount(1, $siteList, 'Site list filtered by tag must contain exactly one item.');
        $this->assertTrue(
            isset($siteList[$this->getSiteId()]),
            'Site list filtered by tag must contain the tagged site.'
        );
    }
}
