<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class NewRelicCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class NewRelicCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NewRelic\EnableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\DisableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\InfoCommand
     *
     * @group new-relic
     * @group long_fixme
     */
    public function testNewRelicInfoEnableDisable()
    {
        $sitename = $this->getSiteName();

        // ENABLE
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
            "Returned data from new-relic:info should have an active state"
        );

        // DISABLE
        $this->terminus("new-relic:disable {$sitename}");
        $info2 = $this->terminusJsonResponse("new-relic:info {$sitename}");
        $this->assertIsArray($info2, "Returned data from new-relic:info should be an array");
        $this->assertArrayHasKey(
            "state",
            $info2,
            "Returned data from new-relic:info should have a state value"
        );
        $this->assertNotEquals(
            "active",
            $info2['state'],
            "Returned data from new-relic:info should not have an active state"
        );
    }
}
