<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
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
    use SiteBaseSetupTrait;
    use LoginHelperTrait;

    /**
     * Test UpstreamListCommand
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\ListCommand
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
     * @group upstream
     * @group short
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
}
