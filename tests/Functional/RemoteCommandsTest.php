<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class RemoteCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class RemoteCommandsTest extends TerminusTestBase
{
    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Temporarily skip this test until we have a way to run it in CI.
        $this->markTestSkipped(
            'A Drupal-based test site is required to test remote Drush commands.'
        );
        // We add this to the environment to ensure that the Terminus
        // commands will not attempt to make any actual SSH connections.
        $this->env['TERMINUS_TEST_MODE'] = true;
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Remote\DrushCommand
     * @covers \Pantheon\Terminus\Commands\Remote\WPCommand
     *
     * @group remote
     * @group short
     */
    public function testDrushCommands()
    {
        $commandPrefix = sprintf('drush %s', $this->getSiteEnv());

        // Test Drush version command with retry
        $drushVersionCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'version');
        $drushVersion = $this->terminusJsonResponse($drushVersionCommand);
        $this->assertIsString($drushVersion);
        $this->assertIsInt(preg_match('(^\d{1,2})', $drushVersion, $matches));
        $this->assertGreaterThanOrEqual(8, $matches[0]);

        // Test Drush status command with retry
        $drushStatusCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'status');
        $drushStatus = $this->terminusJsonResponse($drushStatusCommand);
        $this->assertIsArray($drushStatus);
        $this->assertTrue(isset($drushStatus['drush-version']));
        $this->assertEquals($drushStatus['drush-version'], $drushVersion);

        // Test Drush sql:cli command with retry
        $drushSqlCliCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'sql:cli');
        $drushSqlCliResult = $this->terminusPipeInput(
            $drushSqlCliCommand,
            'echo "select uuid from users where uid=1;"'
        );

        $this->assertEquals(
            1,
            preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $drushSqlCliResult),
            'The "drush sql:cli" execution result should contain a valid v4 UUID.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Remote\WPCommand
     *
     * @group remote
     * @group short
     */
    public function testWPCommands()
    {
        $commandPrefix = sprintf('wp %s', $this->getSiteEnv());

        // Test WP-CLI core version command with retry
        $wpVersionCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'core version');
        $wpVersion = $this->terminusJsonResponse($wpVersionCommand);
        $this->assertIsString($wpVersion);
        $this->assertIsInt(preg_match('(^\d{1,2})', $wpVersion, $matches));
        $this->assertGreaterThanOrEqual(5, $matches[0]);

        // Test WP-CLI core info command with retry
        $wpInfoCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'core info');
        $wpInfo = $this->terminusJsonResponse($wpInfoCommand);
        $this->assertIsArray($wpInfo);
        $this->assertTrue(isset($wpInfo['version']));
        $this->assertEquals($wpInfo['version'], $wpVersion);

        // Test WP-CLI db query command with retry
        $wpDbQueryCommand = sprintf('%s --retry=3 -- %s', $commandPrefix, 'db query');
        $wpDbQueryResult = $this->terminusPipeInput(
            $wpDbQueryCommand,
            'echo "select ID from wp_users where ID=1;"'
        );

        $this->assertEquals(
            1,
            preg_match('/^\d+$/', $wpDbQueryResult),
            'The "wp db query" execution result should contain a valid user ID.'
        );
    }
}
