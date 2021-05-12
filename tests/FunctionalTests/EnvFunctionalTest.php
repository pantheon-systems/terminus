<?php


namespace Pantheon\Terminus\FunctionalTests;


/**
 * Class EnvFunctionalTest
 *
 * @package Pantheon\Terminus\FunctionalTests
 */
class EnvFunctionalTest extends FunctionalTestBase {


    /**
     * @test
     * @testdox Test basic env:info command.
     */
    public function TestEnvInfo() {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $output = $this->terminus("env:info {$site}.dev --format=yaml");
        $this->assertEquals(0, $output['status']);
    }

}
