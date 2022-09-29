<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SiteCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SiteCommandsTest extends TerminusTestBase
{
    /**
     * @var string
     */
    private $mockSiteName;

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (isset($this->mockSiteName)) {
            $this->terminus(
                sprintf('site:delete %s', $this->mockSiteName),
                ['--quiet'],
                false
            );
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\ListCommand
     *
     * @group site
     * @group short
     */
    public function testSiteListCommand()
    {
        $siteList = $this->terminusJsonResponse(sprintf('site:list --org=%s', $this->getOrg()));
        $this->assertIsArray($siteList);
        $this->assertGreaterThan(0, count($siteList));

        $site = array_shift($siteList);
        $this->assertArrayHasKey('id', $site);
        $this->assertArrayHasKey('memberships', $site);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Org\ListCommand
     *
     * @group site
     * @group short
     */
    public function testSiteOrgListCommand()
    {
        $orgList = $this->terminusJsonResponse(sprintf('site:org:list %s', $this->getSiteName()));
        $this->assertIsArray($orgList);
        $this->assertGreaterThan(0, count($orgList));
    }

    /**
     * Test site:create command.
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\CreateCommand
     * @covers \Pantheon\Terminus\Commands\Site\InfoCommand
     *
     * @group site
     * @group long
     */
    public function testSiteCreateInfoCommands()
    {
        $this->mockSiteName = uniqid('site-create-');
        $command = sprintf(
            'site:create %s %s drupal9',
            $this->mockSiteName,
            $this->mockSiteName
        );
        $this->terminus(
            $command,
            [sprintf('--org=%s', $this->getOrg()), '--quiet']
        );

        $siteInfo = $this->terminusJsonResponse(sprintf('site:info %s', $this->mockSiteName));
        $this->assertNotEmpty($siteInfo);
        $this->assertIsArray($siteInfo);
        $this->assertArrayHasKey('organization', $siteInfo);
        $this->assertEquals($this->getOrg(), $siteInfo['organization']);
        $this->assertArrayHasKey('framework', $siteInfo);
        // Format json prints raw value.
        $this->assertEquals('drupal8', $siteInfo['framework']);

        // Format table prints processed value.
        $siteInfoTable = $this->terminus(sprintf('site:info %s', $this->mockSiteName));
        $this->assertStringContainsString('Drupal 8 or later', $siteInfoTable);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\InfoCommand
     *
     * @group site
     * @group short
     */
    public function testSiteInfoNoArgCommand()
    {
        putenv('TERMINUS_SITE=' . $this->getSiteName());
        $output = $this->terminusWithStderrRedirected('site:info -v');
        $this->assertStringContainsString(
            sprintf(
                'Missing "site" argument. Setting to "%s" (from TERMINUS_SITE env var).',
                $this->getSiteName()
            ),
            $output
        );
    }
}
