<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class UpstreamCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class UpstreamCommandsTest extends TerminusTestBase
{
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
        $upstreamList = $this->terminusJsonResponse('upstream:list');
        $this->assertIsArray($upstreamList);
        $upstreamInfo = array_shift($upstreamList);
        $this->assertArrayHasKey('id', $upstreamInfo, 'An upstream should have "id" field.');
        $this->assertArrayHasKey('label', $upstreamInfo, 'An upstream should have "label" field.');
        $this->assertArrayHasKey('machine_name', $upstreamInfo, 'An upstream should have "machine_name" field.');
        $this->assertArrayHasKey('type', $upstreamInfo, 'An upstream should have "type" field.');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\InfoCommand
     *
     * @group upstream
     * @group short
     */
    public function testUpstreamInfoCommand()
    {
        $upstreamList = $this->terminusJsonResponse('upstream:list');
        $this->assertIsArray($upstreamList);
        $upstream = array_shift($upstreamList);
        $this->assertArrayHasKey('id', $upstream, 'An upstream should have "id" field.');
        $upstreamInfo = $this->terminusJsonResponse(sprintf('upstream:info %s', $upstream['id']));
        $this->assertArrayHasKey('id', $upstreamInfo, 'An upstream should have "id" field.');
        $this->assertArrayHasKey('label', $upstreamInfo, 'An upstream should have "label" field.');
        $this->assertArrayHasKey('machine_name', $upstreamInfo, 'An upstream should have "machine_name" field.');
        $this->assertArrayHasKey('type', $upstreamInfo, 'An upstream should have "type" field.');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Upstream\Updates\ListCommand
     * @covers \Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand
     *
     * @group upstream
     * @group short
     */
    public function testUpstreamUpdatesListStatus()
    {
        $updatesList = $this->terminusJsonResponse(sprintf('upstream:updates:list %s', $this->getSiteEnv()));
        $this->assertIsArray($updatesList);
        $status = $this->terminus(sprintf('upstream:updates:status %s', $this->getSiteEnv()));
        if (count($updatesList) == 0) {
            $this->assertEquals(
                'current',
                $status,
                'If no updates detected, the "status" field should have "current" value.'
            );
        }
        if (count($updatesList) >= 1) {
            $this->assertNotEquals(
                'current',
                $status,
                'If updates detected, the "status" fields should not have "current" value.'
            );
        }
    }
}
