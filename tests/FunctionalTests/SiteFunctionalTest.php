<?php


namespace Pantheon\Terminus\FunctionalTests;


use PHPUnit\Framework\TestCase;

/**
 * Class SiteFunctionalTest
 *
 * @package Pantheon\Terminus\FunctionalTests
 */
class SiteFunctionalTest extends FunctionalTestBase {

    /**
     * @test
     * @testdox Test to see if we can use terminus.phar and get rational results back from the Hermes API.
     */
    public function testSiteCreate()
    {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $result = $this->terminus("site:info $site --format=yaml");
        $this->assertTrue($result->isSuccess(), "Command should produce success code");
        $this->assertContains('framework: wordpress', $result->__toString());
    }

    /**
     * @test
     * @testdox Test to see if we can use terminus.phar and get rational results back from the Hermes API.
     */
    public function testSiteInfo()
    {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $result = $this->terminus("site:info $site --format=yaml");
        $this->assertTrue($result->isSuccess(), "Command should produce success code");
        $this->assertContains('framework: wordpress', $result->__toString());
    }

}
