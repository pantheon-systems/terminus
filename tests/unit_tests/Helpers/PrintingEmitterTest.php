<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\PrintingEmitter;
use Pantheon\Terminus\UnitTests\TerminusTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class PrintingEmitterTest extends TerminusTestCase
{
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
     */
    public function testPrintingEmitter()
    {
        $alias_replacements = AliasFixtures::aliasReplacementsFixture();
        $buffer = new BufferedOutput();

        $emitter = new PrintingEmitter($buffer);
        $emitter->write($alias_replacements);
        $actual = $buffer->fetch();

        $expected = AliasFixtures::load('drushrcEmitter/standardAliasFixtureWithoutDbUrl.out');

        $this->assertEquals(trim($expected), trim($actual));
    }
}
