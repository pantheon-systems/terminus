<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class RepositoryCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class RepositoryCommandsTest extends TerminusTestBase
{
    /**
     * @test
     *
     * @group repository
     * @group short
     */
    public function testRepositoryCommandsExist()
    {
        $this->assertCommandExists('node:builds:list');
        $this->assertCommandExists('node:builds:rebuild');
        $this->assertCommandExists('node:builds:rollback');
        $this->assertCommandExists('vcs:connection:add');
        $this->assertCommandExists('vcs:connection:link');
        $this->assertCommandExists('vcs:connection:list');
        $this->assertCommandExists('site:pause-builds');
        $this->assertCommandExists('site:resume-builds');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\CreateCommand
     *
     * @group repository
     * @group short
     */
    public function testSiteCreateHasVcsOptions()
    {
        $helpOutput = $this->terminus('help site:create');
        $this->assertStringContainsString('--vcs-provider', $helpOutput);
        $this->assertStringContainsString('--vcs-org', $helpOutput);
        $this->assertStringContainsString('--visibility', $helpOutput);
        $this->assertStringContainsString('--repository-name', $helpOutput);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Node\BuildsListCommand
     *
     * @group repository
     * @group long
     */
    public function testNodeBuildsListCommand()
    {
        $siteName = $this->getNodeSiteName();
        if (empty($siteName)) {
            $this->markTestSkipped('TERMINUS_SITE_NODE environment variable not set.');
        }

        $buildsList = $this->terminusJsonResponse(
            sprintf('node:builds:list %s.dev', $siteName)
        );
        $this->assertIsArray($buildsList);
        $this->assertNotEmpty($buildsList, 'Node.js site should have at least one build.');

        $build = array_shift($buildsList);
        $this->assertArrayHasKey('id', $build);
        $this->assertArrayHasKey('status', $build);
        $this->assertArrayHasKey('branch', $build);
        $this->assertArrayHasKey('commit', $build);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Vcs\Connection\ListCommand
     *
     * @group repository
     * @group long
     */
    public function testVcsConnectionListCommand()
    {
        $orgId = $this->getOrg();
        if (empty($orgId)) {
            $this->markTestSkipped('TERMINUS_ORG environment variable not set.');
        }

        $output = $this->terminus(
            sprintf('vcs:connection:list %s', $orgId)
        );
        $this->assertNotEmpty($output);
    }

    /**
     * Returns the Node.js site name for testing.
     *
     * @return string
     */
    protected static function getNodeSiteName(): string
    {
        return getenv('TERMINUS_SITE_NODE') ?: '';
    }
}
