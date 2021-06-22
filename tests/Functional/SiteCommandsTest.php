<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\ValidUuidTrait;
use PHPUnit\Framework\TestCase;

class SiteCommandsTest extends TestCase
{
    use ValidUuidTrait;
    use TerminusTestTrait;
    use SiteBaseSetupTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\ListCommand
     * @group site
     * @group short
     */
    public function testSiteInfoCommand()
    {
        $org = getenv("TERMINUS_ORG");
        $siteList = $this->terminusJsonResponse(
            "site:list --org=" . $org
        );
        $this->assertIsArray(
            $siteList,
            "Response from site:info should be an array of values"
        );
        $this->assertGreaterThan(
            0,
            count($siteList),
            "count of sites should be a non-zero number"
        );
        $site = array_shift($siteList);
        $this->assertArrayHasKey(
            'id',
            $site,
            "Response from site should contain an ID property"
        );

        $this->assertEquals(
            $org,
            $site['organization']
        );
    }

    /**
     * Test Site Org List command
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Org\ListCommand
     * @throws \JsonException
     * @group site
     * @group short
     */
    public function testSiteOrgListCommand()
    {
        $sitename = getenv('TERMINUS_SITE');
        $list = $this->terminusJsonResponse("site:org:list {$sitename}");
        $this->assertIsArray(
            $list,
            "Response from Site List should be an array"
        );
        $this->assertGreaterThan(
            0,
            count($list),
            "count of sites should be a non-zero number"
        );
    }

    /**
     * Test Site Orgs command
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\Org\ListCommand
     * @throws \JsonException
     * @group site
     * @group short
     */
    public function testSiteOrgsCommand()
    {
        $sitename = getenv('TERMINUS_SITE');
        $list = $this->terminusJsonResponse("site:orgs {$sitename}");
        $this->assertIsArray(
            $list,
            "Response from Site List should be an array"
        );
        $this->assertGreaterThan(
            0,
            count($list),
            "count of sites should be a non-zero number"
        );
    }

    /**
     * Test site:create command.
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\CreateCommand
     * @covers \Pantheon\Terminus\Commands\Site\DeleteCommand
     * @covers \Pantheon\Terminus\Commands\Site\InfoCommand
     * @group site
     * @group long
     */
    public function testSiteCreateInfoDeleteCommand()
    {
        $sitename = \uniqid(__METHOD__ . "-");
        $org = getenv('TERMINUS_SITE');

        $this->terminus(
            vprintf(
                'site:create %s %s, drupal9 --org=%s',
                [ $sitename, $sitename, $org ]
            ),
            null
        );
        sleep(10);
        $info = $this->terminusJsonResponse(
            vprintf(
                "site:info %s",
                [$sitename]
            )
        );
        $this->assertEquals($org, $info['organization']);
        $this->terminus(
            sprintf(
                'site:delete %d',
                [$sitename]
            ),
            null
        );
    }

}
