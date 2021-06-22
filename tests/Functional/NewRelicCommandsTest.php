<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class BackupCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class NewRelicCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NewRelic\EnableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\DisableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\InfoCommand
     *
     * @group new-relic
     * @gropu short
     */
    public function testNewRelicInfoEnableDisable()
    {
        $sitename = getenv('TERMINUS_SITE');
        $this->terminus("new-relic:enable {$sitename}");
        $info = $this->terminusJsonResponse("new-relic:info {$sitename}");
        $this->assertIsArray($info, "Returned data from new-relic:info should be an array");
        $this->assertArrayHasKey(
            "state",
            $info,
            "Returned data from new-relic:info should have a state value"
        );
        $this->assertEquals(
            "active",
            $info['state'],
            "Returned data from new-relic:info should have a an active state"
        );
    }
}
