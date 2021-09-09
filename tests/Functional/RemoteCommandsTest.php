<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class RemoteCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class RemoteCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        if (!$this->isSiteFrameworkDrupal()) {
            $this->markTestSkipped(
                'A Drupal-based test site is required to test remote Drush commands.'
            );
        }
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

        $command = sprintf('%s -- %s', $commandPrefix, 'version');
        $drushVersion = $this->terminusJsonResponse($command);
        $this->assertIsString($drushVersion);
        $this->assertIsInt(preg_match('(^\d{1,2})', $drushVersion, $matches));
        $this->assertGreaterThanOrEqual(8, $matches[0]);

        $command = sprintf('%s -- %s', $commandPrefix, 'status');
        $drushStatus = $this->terminusJsonResponse($command);
        $this->assertIsArray($drushStatus);
        $this->assertTrue(isset($drushStatus['drush-version']));
        $this->assertEquals($drushStatus['drush-version'], $drushVersion);
    }
}
