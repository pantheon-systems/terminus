<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class SelfCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SelfCommandsTest extends TerminusTestBase
{
    protected const SELF_UPDATE_COMMAND = 'self:update';

    /**
     * @test
     *
     * @group self
     * @group long
     */
    public function testSelfUpdateCommand()
    {
        $this->assertCommandExists(self::SELF_UPDATE_COMMAND);

        // Test that the command works when plugins are not installed.
        [$output, $exitCode, $error] = static::callTerminus(self::SELF_UPDATE_COMMAND);
        if (
            0 !== $exitCode
            && false !== strpos($error, 'rate limit exceeded')
        ) {
            // @todo: fix CMS-972
            $this->markTestSkipped(sprintf('Failed executing %s command: %s', self::SELF_UPDATE_COMMAND, $error));
        }

        $this->assertEquals('No update available', $output);

        // Test that the command works when plugins are installed.
        $this->terminus('self:plugin:uninstall pantheon-systems/terminus-plugin-example', [], false);
        $pluginList = $this->terminusWithStderrRedirected(
            'self:plugin:install pantheon-systems/terminus-plugin-example'
        );
        $this->assertStringContainsString(
            'Installed pantheon-systems/terminus-plugin-example',
            $pluginList,
            'Failed installing plugins to setup self:update command test.'
        );
        $output = $this->terminus(self::SELF_UPDATE_COMMAND);
        $this->assertEquals('No update available', $output);
    }
}
