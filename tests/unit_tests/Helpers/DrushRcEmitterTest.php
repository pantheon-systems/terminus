<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\AliasesDrushRcEmitter;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

class DrushRcEmitterTest extends TerminusTestCase
{
    /**
     * testDrushrcEmitter confirms that the alias collection sorts
     * its inputs correctly
     *
     * @param string $expectedPath
     *   Relative path to fixture file containing the expected test result.
     * @param array $rawAliasData
     *   Fixture data to use to generate a test alias.
     * @param bool $withDbUrl
     *   Whether or not to include database information.
     */
    public function testDrushrcEmitter()
    {
        $alias_replacements = AliasFixtures::aliasReplacementsFixture();
        $base_dir = AliasFixtures::mktmpdir() . '/.drush/';
        $location = $base_dir . '/pantheon.aliases.drushrc.php';

        $emitter = new AliasesDrushRcEmitter($location, $base_dir);
        $emitter->write($alias_replacements);
        $this->assertFileExists($location);
        $actual = file_get_contents($location);

        $expected = AliasFixtures::load('drushrcEmitter/standardAliasFixtureWithoutDbUrl.out');

        $this->assertEquals(trim($expected), trim($actual));
    }
}
