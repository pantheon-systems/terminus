<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\DrushSitesYmlEmitter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class SitesYmlEmitterTest extends TestCase
{
    /**
     * sitesYmlEmitterValues provides the expected results and inputs for testSitesYmlEmitter
     */
    public function sitesYmlEmitterValues()
    {
        return [
            [
                'standardWithDbUrl',
                [
                    'drush.yml',
                    'sites/pantheon/agency.site.yml',
                    'sites/pantheon/demo.site.yml',
                    'sites/pantheon/personalsite.site.yml',
                ],
                AliasFixtures::standardAliasFixture(),
                true,
            ],

            [
                'standardWithoutDbUrl',
                [
                    'drush.yml',
                    'sites/pantheon/agency.site.yml',
                    'sites/pantheon/demo.site.yml',
                    'sites/pantheon/personalsite.site.yml',
                ],
                AliasFixtures::standardAliasFixture(),
                false,
            ],
        ];
    }

    /**
     * testSitesYmlEmitter confirms that the alias collection sorts
     * its inputs correctly
     *
     * @dataProvider sitesYmlEmitterValues
     */
    public function testSitesYmlEmitter($expectedBaseDir, $expectedPathList, $rawAliasData, $withDbUrl)
    {
        $aliasCollection = AliasFixtures::aliasCollection($rawAliasData, $withDbUrl);
        $home = AliasFixtures::mktmpdir();
        $base = $home . '/drush';

        $emitter = new DrushSitesYmlEmitter($base, $home);
        $emitter->write($aliasCollection);

        $this->assertNotEmpty($expectedPathList);
        foreach ($expectedPathList as $path) {
            $location = "$base/$path";
            $this->assertFileExists($location);
            $actual = file_get_contents($location);

            $expected = AliasFixtures::load("sitesYmlEmitter/$expectedBaseDir/$path");
            $this->assertEquals("$path:\n" . trim($expected), "$path:\n" . trim($actual));
        }
    }
}
