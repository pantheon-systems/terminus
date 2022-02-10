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
     * @group short
     */
    public function testSelfUpdateCommand()
    {
        $this->assertCommandExists(self::SELF_UPDATE_COMMAND);

        // Test that the command works when plugins are not installed.
        $output = $this->terminus(self::SELF_UPDATE_COMMAND);
        $this->assertEquals('No update available', $output);

        // Test that the command works when plugins are installed.
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
