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
        if (!$this->isSiteFrameworkDrupal()) {
            $this->markTestSkipped(
                'A Drupal-based test site is required to test remote Drush commands.'
            );
        }
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

        $drushVersionCommand = sprintf('%s -- %s', $commandPrefix, 'version');
        $drushVersion = $this->terminusJsonResponse($drushVersionCommand);
        $this->assertIsString($drushVersion);
        $this->assertIsInt(preg_match('(^\d{1,2})', $drushVersion, $matches));
        $this->assertGreaterThanOrEqual(8, $matches[0]);

        $drushStatusCommand = sprintf('%s -- %s', $commandPrefix, 'status');
        $drushStatus = $this->terminusJsonResponse($drushStatusCommand);
        $this->assertIsArray($drushStatus);
        $this->assertTrue(isset($drushStatus['drush-version']));
        $this->assertEquals($drushStatus['drush-version'], $drushVersion);

        $drushSqlCliCommand = sprintf('%s -- %s', $commandPrefix, 'sql:cli');
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
}
