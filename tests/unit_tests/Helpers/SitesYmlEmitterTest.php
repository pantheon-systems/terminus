<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\DrushSitesYmlEmitter;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

class SitesYmlEmitterTest extends TerminusTestCase
{
    /**
     * Test Drush 9 alias emitter
     */
    public function testSitesYmlEmitter()
    {
        $alias_replacements = AliasFixtures::aliasReplacementsFixture();
        $home = AliasFixtures::mktmpdir();
        $base = $home . '/drush';

        $emitter = new DrushSitesYmlEmitter($base, $home);
        $emitter->write($alias_replacements);

        $expectedPathList = [
            'drush.yml',
            'sites/pantheon/agency.site.yml',
            'sites/pantheon/demo.site.yml',
            'sites/pantheon/personalsite.site.yml',
        ];
        foreach ($expectedPathList as $path) {
            $location = "$base/$path";
            $this->assertFileExists($location);
            $actual = file_get_contents($location);

            $expected = AliasFixtures::load("sitesYmlEmitter/standardWithoutDbUrl/$path");
            $this->assertEquals("$path:\n" . trim($expected), "$path:\n" . trim($actual));
        }
    }
}
