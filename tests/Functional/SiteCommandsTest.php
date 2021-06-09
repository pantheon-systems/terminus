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
     * @covers \Pantheon\Terminus\Commands\Site\InfoCommand
     * @group site
     * @group short
     */
    public function testSiteInfoCommand()
    {
        $sitename = getenv('TERMINUS_SITE');
        $siteInfo = $this->terminusJsonResponse(
            "site:info {$sitename}"
        );
        $this->assertIsArray(
            $siteInfo,
            "Response from site:info should be an array of values"
        );
        $this->assertArrayHasKey(
            'id',
            $siteInfo,
            "Response from site should contain an ID property"
        );

        $this->assertEquals(
            $this->org,
            $siteInfo['organization']
        );
    }

    /**
     * Test Site List command
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Site\ListCommand
     * @throws \JsonException
     * @group site
     * @group short
     */
    public function testSiteListCommand()
    {
        $list = $this->terminusJsonResponse("site:list --org=" . getenv('TERMINUS_ORG'));
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
     * @group site
     * @group long
     */
    public function testSiteCreateCommand()
    {
        // TODO:
        $this->fail("To be written");
    }

    /**
     * Test Site:delete command.
     *
     * @test
     * @group site
     * @group long
     */
    public function testSiteDeleteCommand()
    {
        //TODO:
        $this->fail("To be written");
    }
}
