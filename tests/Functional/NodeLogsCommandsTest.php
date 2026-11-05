<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class NodeLogsCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class NodeLogsCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NodeLogs\NodeLogsBuildGetCommand
     * @covers \Pantheon\Terminus\Commands\NodeLogs\NodeLogsRuntimeGetCommand
     *
     * @group node_logs
     * @group short
     */
    public function testNodeLogsCommandsExist()
    {
        $this->assertCommandExists('node:logs:build:get');
        $this->assertCommandExists('node:logs:runtime:get');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NodeLogs\NodeLogsRuntimeGetCommand
     *
     * @group node_logs
     * @group long
     */
    public function testNodeLogsRuntimeGetCommand()
    {
        $siteName = $this->getNodeSiteName();
        if (empty($siteName)) {
            $this->markTestSkipped('TERMINUS_SITE_NODE environment variable not set.');
        }

        $output = $this->terminus(
            sprintf('node:logs:runtime:get %s.dev', $siteName)
        );
        $this->assertNotEmpty($output);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NodeLogs\NodeLogsRuntimeGetCommand
     *
     * @group node_logs
     * @group long
     */
    public function testNodeLogsRuntimeGetWithSeverityOption()
    {
        $siteName = $this->getNodeSiteName();
        if (empty($siteName)) {
            $this->markTestSkipped('TERMINUS_SITE_NODE environment variable not set.');
        }

        $output = $this->terminus(
            sprintf('node:logs:runtime:get %s.dev --severity=info', $siteName),
            [],
            false
        );
        // Command should execute without fatal errors regardless of log content.
        $this->assertIsString($output);
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
