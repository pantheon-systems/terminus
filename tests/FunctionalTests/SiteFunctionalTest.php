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
        $org = getenv('PANTHEON_INTERNAL_ORG');
        $result = $this->terminus(
            sprintf('site:create %s %s drupal9 --org=%s', $site, $site, $org)
        );
        $this->assertTrue(
            $result->isSuccess(),
            "Command Should return clean status"
        );
    }

    /**
     * @test
     * @testdox Test to see if we can use terminus.phar and get rational results back from the Hermes API.
     */
    public function testSiteInfo()
    {
       $this->fail();
    }

    /**
     * @test
     * @testdox Test to see if we can use terminus.phar and get rational results back from the Hermes API.
     */
    public function testSiteUpdate()
    {
       $this->fail();
    }

    /**
     * @test
     * @testdox Test to see if we can use terminus.phar and get rational results back from the Hermes API.
     */
    public function testSiteDelete()
    {
        $this->fail();
    }

}
