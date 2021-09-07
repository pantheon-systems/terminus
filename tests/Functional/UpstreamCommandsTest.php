<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class UpstreamCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class UpstreamCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * Test UpstreamListCommand
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\ListCommand
     *
     * @group upstream
     * @group short
     */
    public function testUpstreamListCommand()
    {

        $upstreamList = $this->terminusJsonResponse("upstream:list");
        $this->assertIsArray(
            $upstreamList,
            "Response from upstream list should be unserialized json"
        );
        $upstreamInfo = array_shift($upstreamList);
        $this->assertArrayHasKey('id', $upstreamInfo, "Upstream data should have an id");
        $this->assertArrayHasKey('label', $upstreamInfo, "Upstream data should have a name");
        $this->assertArrayHasKey('machine_name', $upstreamInfo, "Upstream data should have a machine_name");
        $this->assertArrayHasKey('type', $upstreamInfo, "Upstream data should have a type");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\InfoCommand
     *
     * @group upstream
     * @group short
     *
     * @throws \JsonException
     */
    public function testUpstreamInfoCommand()
    {
        $upstreamList = $this->terminusJsonResponse("upstream:list");
        $this->assertIsArray(
            $upstreamList,
            "Response from upstream list should be unserialized json"
        );
        $upstream = array_shift($upstreamList);
        $upstreamInfo = $this->terminusJsonResponse("upstream:info " . $upstream['id']);
        $this->assertArrayHasKey('id', $upstreamInfo, "Upstream data should have an id");
        $this->assertArrayHasKey('label', $upstreamInfo, "Upstream data should have a name");
        $this->assertArrayHasKey('machine_name', $upstreamInfo, "Upstream data should have a machine_name");
        $this->assertArrayHasKey('type', $upstreamInfo, "Upstream data should have a type");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\Updates\ListCommand
     * @covers \Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand
     *
     * @group upstream
     * @group short
     *
     * @throws \JsonException
     */
    public function testUpstreamUpdatesListStatus()
    {
        $sitename = $this->getSiteName();
        $updatesList = $this->terminusJsonResponse("upstream:updates:list {$sitename}.dev", null);
        $this->assertIsArray(
            $updatesList,
            'Response from upstream list should be unserialized json'
        );
        $status = $this->terminus("upstream:updates:status {$sitename}.dev");
        if (count($updatesList) == 0) {
            $this->assertEquals("current", $status, "if there are no updates, the status should be 'current'.");
        }
        if (count($updatesList) >= 1) {
            $this->assertNotEquals('current', $status, "if there are no updates, the status should NOT be 'current'.");
        }
    }
}
