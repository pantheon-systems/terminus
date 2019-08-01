<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\AliasesDrushRcEmitter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class DrushRcEmitterTest extends TestCase
{
    /**
     * drushrcEmitterValues provides the expected results and inputs for testDrushrcEmitter
     *
     * @return array
     */
    public function drushrcEmitterValues()
    {
        return [
            [
                'standardAliasFixtureWithDbUrl.out',
                AliasFixtures::standardAliasFixture(),
                true,
            ],

            [
                'standardAliasFixtureWithoutDbUrl.out',
                AliasFixtures::standardAliasFixture(),
                false,
            ],
        ];
    }

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
     *
     * @dataProvider drushrcEmitterValues
     */
    public function testDrushrcEmitter($expectedPath, $rawAliasData, $withDbUrl)
    {
        $aliasCollection = AliasFixtures::aliasCollection($rawAliasData, $withDbUrl);
        $base_dir = AliasFixtures::mktmpdir() . '/.drush/';
        $location = $base_dir . '/pantheon.aliases.drushrc.php';

        $emitter = new AliasesDrushRcEmitter($location, $base_dir);
        $emitter->write($aliasCollection);
        $this->assertFileExists($location);
        $actual = file_get_contents($location);
        $expected = AliasFixtures::load('drushrcEmitter/' . $expectedPath);

        $this->assertEquals(trim($expected), trim($actual));
    }
}
