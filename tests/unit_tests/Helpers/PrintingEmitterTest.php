<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\PrintingEmitter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class PrintingEmitterTest extends TestCase
{
    /**
     * printingEmitterValues provides the expected results and inputs for testPrintingEmitter
     *
     * @return array
     */
    public function printingEmitterValues()
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
     * testPrintingEmitter confirms that the alias collection sorts
     * its inputs correctly
     *
     * @param string $expectedPath
     *   Relative path to fixture file containing the expected test result.
     * @param array $rawAliasData
     *   Fixture data to use to generate a test alias.
     * @param bool $withDbUrl
     *   Whether or not to include database information.
     *
     * @dataProvider printingEmitterValues
     */
    public function testPrintingEmitter($expectedPath, $rawAliasData, $withDbUrl)
    {
        $aliasCollection = AliasFixtures::aliasCollection($rawAliasData, $withDbUrl);
        $buffer = new BufferedOutput();

        $emitter = new PrintingEmitter($buffer);
        $emitter->write($aliasCollection);
        $actual = $buffer->fetch();
        $expected = AliasFixtures::load('drushrcEmitter/' . $expectedPath);

        $this->assertEquals(trim($expected), trim($actual));
    }
}
