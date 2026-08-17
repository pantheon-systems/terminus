<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SiteTeamCommandsTest.
 *
 * Every test in this class establishes its own team-membership precondition via
 * ensureTeamMembership()/ensureNotTeamMember(), so the tests may be run in any
 * order, individually, or filtered by group without depending on state left
 * behind by a sibling test.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SiteTeamCommandsTest extends TerminusTestBase
{
    private const TEST_SITE_TAG = 'test-site-list';

    private const ROLE_TEAM_MEMBER = 'team_member';

    private const ROLE_DEVELOPER = 'developer';

    private const ROLE_SITE_ADMIN = 'site_admin';

    /**
     * The email address of the user performing the commands, i.e. the owner of
     * the machine token in use. This is the actor recorded in the audit trail,
     * and is distinct from getUserEmail(), which is the user being managed.
     *
     * @var string|null
     */
    private static ?string $actorEmail = null;

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
     * Asserts the role validator accepts site_admin and reports it as a valid
     * selection. Guards against a regression of WKM-7430/WKM-7431, which added
     * site_admin to RoleValidator::SITE_ROLES.
     *
     * @test
     * @covers \Pantheon\Terminus\Hooks\RoleValidator
     *
     * @group site-team
     * @group short
     */
    public function testSiteTeamRoleValidatorAcceptsSiteAdmin(): void
    {
        [, $exitCode, $stderr] = self::callTerminus(
            sprintf(
                'site:team:role %s %s not_a_real_role --yes',
                $this->getSiteName(),
                $this->getUserEmail()
            ),
            null,
            $this->env
        );

        $this->assertNotEquals(0, $exitCode, 'An invalid role must be rejected.');
        $this->assertStringContainsString('is not a valid role selection', $stderr);
        $this->assertStringContainsString(
            self::ROLE_SITE_ADMIN,
            $stderr,
            'site_admin must be listed among the valid site roles.'
        );
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
        $this->ensureNotTeamMember();

        $this->terminus(
            sprintf('site:team:add %s %s', $this->getSiteName(), $this->getUserEmail())
        );

        $team = $this->terminusJsonResponse(sprintf('site:team:list %s', $this->getSiteName()));
        $this->assertIsArray($team);
        $this->assertNotEmpty($team);
        $emails = array_column($team, 'email');
        $this->assertContains($this->getUserEmail(), $emails);

        $this->terminus(sprintf('site:team:remove %s %s', $this->getSiteName(), $this->getUserEmail()));
        $this->assertNull(
            $this->getTeamMemberRole($this->getUserEmail()),
            'The user must no longer be a member of the site team.'
        );
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

        $this->ensureNotTeamMember();
        $this->assertSiteListContainsTaggedSite();

        $this->ensureTeamMembership(self::ROLE_TEAM_MEMBER);
        $this->assertSiteListContainsTaggedSite();
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\RoleCommand
     *
     * @group site-team
     * @group long
     */
    public function testSiteTeamRoleCommand(): void
    {
        $this->ensureTeamMembership(self::ROLE_DEVELOPER);

        $this->terminus(
            sprintf('site:team:role %s %s %s', $this->getSiteName(), $this->getUserEmail(), self::ROLE_TEAM_MEMBER)
        );

        $this->assertTeamMemberRoleEquals($this->getUserEmail(), self::ROLE_TEAM_MEMBER);
    }

    /**
     * Asserts a user can be added to a site team directly as a site_admin, that
     * the role sticks, and that the action is written to the audit trail.
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\AddCommand
     *
     * @group site-team
     * @group long
     */
    public function testSiteTeamAddCommandWithSiteAdmin(): void
    {
        $this->ensureNotTeamMember();

        $workflowIdsBefore = $this->getWorkflowIds();
        $this->terminus(
            sprintf('site:team:add %s %s %s', $this->getSiteName(), $this->getUserEmail(), self::ROLE_SITE_ADMIN)
        );

        $this->assertTeamMemberRoleEquals($this->getUserEmail(), self::ROLE_SITE_ADMIN);
        $this->assertAuditWorkflowLogged($workflowIdsBefore);
    }

    /**
     * Asserts an existing team member can be promoted to site_admin, that the
     * role sticks, and that the action is written to the audit trail.
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\RoleCommand
     *
     * @group site-team
     * @group long
     */
    public function testSiteTeamRoleCommandWithSiteAdmin(): void
    {
        $this->ensureTeamMembership(self::ROLE_TEAM_MEMBER);

        $workflowIdsBefore = $this->getWorkflowIds();
        $this->terminus(
            sprintf('site:team:role %s %s %s', $this->getSiteName(), $this->getUserEmail(), self::ROLE_SITE_ADMIN)
        );

        $this->assertTeamMemberRoleEquals($this->getUserEmail(), self::ROLE_SITE_ADMIN);
        $this->assertAuditWorkflowLogged($workflowIdsBefore);
    }

    /**
     * Asserts a site_admin can be removed from a site team and that the removal
     * is written to the audit trail.
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Team\RemoveCommand
     *
     * @group site-team
     * @group long
     */
    public function testSiteTeamRemoveCommandWithSiteAdmin(): void
    {
        $this->ensureTeamMembership(self::ROLE_SITE_ADMIN);

        $workflowIdsBefore = $this->getWorkflowIds();
        $this->terminus(
            sprintf('site:team:remove %s %s', $this->getSiteName(), $this->getUserEmail())
        );

        $this->assertNull(
            $this->getTeamMemberRole($this->getUserEmail()),
            'The site_admin must no longer be a member of the site team.'
        );
        $this->assertAuditWorkflowLogged($workflowIdsBefore);
    }

    /**
     * Ensures the test user holds the given role on the site team, adding or
     * updating the membership only as needed.
     *
     * @param string $role
     *   The role the test user must hold.
     */
    private function ensureTeamMembership(string $role): void
    {
        $currentRole = $this->getTeamMemberRole($this->getUserEmail());

        if (null === $currentRole) {
            $this->terminus(
                sprintf('site:team:add %s %s %s', $this->getSiteName(), $this->getUserEmail(), $role)
            );
        } elseif ($currentRole !== $role) {
            $this->terminus(
                sprintf('site:team:role %s %s %s', $this->getSiteName(), $this->getUserEmail(), $role)
            );
        }

        $this->assertTeamMemberRoleEquals($this->getUserEmail(), $role);
    }

    /**
     * Ensures the test user is not a member of the site team.
     */
    private function ensureNotTeamMember(): void
    {
        if (null === $this->getTeamMemberRole($this->getUserEmail())) {
            return;
        }

        $this->terminus(sprintf('site:team:remove %s %s', $this->getSiteName(), $this->getUserEmail()));
        $this->assertNull(
            $this->getTeamMemberRole($this->getUserEmail()),
            'The user must not be a member of the site team.'
        );
    }

    /**
     * Returns the role the given user holds on the site team.
     *
     * @param string $email
     *   The email address of the team member.
     *
     * @return string|null
     *   The role, or NULL if the user is not a member of the team.
     */
    private function getTeamMemberRole(string $email): ?string
    {
        $team = $this->terminusJsonResponse(sprintf('site:team:list %s', $this->getSiteName()));
        $this->assertIsArray($team);

        foreach ($team as $member) {
            if (isset($member['email']) && $member['email'] === $email) {
                return $member['role'] ?? null;
            }
        }

        return null;
    }

    /**
     * Asserts the given user holds the given role, allowing for the membership
     * change to become visible.
     *
     * @param string $email
     *   The email address of the team member.
     * @param string $role
     *   The expected role.
     */
    private function assertTeamMemberRoleEquals(string $email, string $role): void
    {
        $this->assertTerminusCommandResultEqualsInAttempts(
            fn () => $this->getTeamMemberRole($email),
            $role,
            6,
            5
        );
    }

    /**
     * Returns the IDs of the workflows currently logged for the test site.
     *
     * @return array
     *   The list of workflow IDs.
     */
    private function getWorkflowIds(): array
    {
        $workflows = $this->terminusJsonResponse(sprintf('workflow:list %s', $this->getSiteName()));
        $this->assertIsArray($workflows);

        return array_column($workflows, 'id');
    }

    /**
     * Asserts a team management action was written to the site's audit trail,
     * attributed to the acting user, and that the entry can be resolved by ID
     * the same way the Dashboard's "View details" resolves it.
     *
     * @param array $previousWorkflowIds
     *   The workflow IDs present before the action was performed.
     * @param string $expectedStatus
     *   The expected workflow status. Actions blocked by the backend are still
     *   logged, but Workflow::getStatus() only reports running/failed/succeeded,
     *   so an aborted workflow — shown as "Canceled" in the Dashboard — reads as
     *   "failed" here. The abort reason is not exposed by workflow:list or
     *   workflow:info:status; it is only surfaced in the failing command's own
     *   output, via Workflow::getMessage().
     *
     * @return array
     *   The audit trail entry.
     */
    private function assertAuditWorkflowLogged(
        array $previousWorkflowIds,
        string $expectedStatus = 'succeeded'
    ): array {
        $newWorkflows = [];
        $attempts = 12;
        do {
            $workflows = $this->terminusJsonResponse(sprintf('workflow:list %s', $this->getSiteName()));
            $this->assertIsArray($workflows);

            $newWorkflows = array_values(array_filter(
                $workflows,
                fn ($workflow): bool => !in_array($workflow['id'] ?? null, $previousWorkflowIds, true)
            ));
            if ($newWorkflows) {
                break;
            }

            sleep(5);
        } while (--$attempts > 0);

        $this->assertNotEmpty(
            $newWorkflows,
            'A workflow must be logged for the team management action.'
        );

        // workflow:list returns the most recently created workflow first.
        $workflow = reset($newWorkflows);

        $fields = [
            'id',
            'env',
            'workflow',
            'user',
            'status',
            'started_at',
            'finished_at',
            'time',
        ];
        foreach ($fields as $field) {
            $this->assertArrayHasKey(
                $field,
                $workflow,
                sprintf('The audit trail entry should have a "%s" field', $field)
            );
        }

        $this->assertNotEmpty(
            $workflow['workflow'],
            'The audit trail entry must carry a description of the action.'
        );
        $this->assertEquals(
            $this->getActorEmail(),
            $workflow['user'],
            'The audit trail entry must attribute the action to the acting user.'
        );
        $this->assertEquals($expectedStatus, $workflow['status']);

        $status = $this->terminusJsonResponse(
            sprintf('workflow:info:status %s --id=%s', $this->getSiteName(), $workflow['id'])
        );
        $this->assertIsArray($status);
        $this->assertEquals(
            $workflow['id'],
            $status['id'],
            'The audit trail entry must be resolvable by workflow ID.'
        );

        return $workflow;
    }

    /**
     * Returns the email address of the user performing the commands, i.e. the
     * actor recorded in the audit trail.
     *
     * @return string
     *   The email address.
     */
    private function getActorEmail(): string
    {
        if (null === self::$actorEmail) {
            $whoami = $this->terminusJsonResponse('auth:whoami');
            $this->assertIsArray($whoami);
            $this->assertArrayHasKey('email', $whoami);
            self::$actorEmail = $whoami['email'];
        }

        return self::$actorEmail;
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
