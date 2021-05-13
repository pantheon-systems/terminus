<?php


namespace Pantheon\Terminus\FunctionalTests;

/**
 * Class EnvFunctionalTest
 *
 * @package Pantheon\Terminus\FunctionalTests
 */
class EnvFunctionalTest extends FunctionalTestBase
{

    /**
     * @test
     * @requires SiteFunctionTest::testSiteCreate
     */
    public function testEnvCreate()
    {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $org = getenv('PANTHEON_INTERNAL_ORG');
        $result = $this->terminus(
            sprintf('env:create %s %s', $site, 'env-functional-test')
        );
        $this->assertTrue(
            $result->isSuccess(),
            "Command Should return clean status"
        );
    }

    /**
     * @requires testEnvCreate
     * @test
     * @testdox Test basic env:info command.
     */
    public function testEnvInfo()
    {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $org = getenv('PANTHEON_INTERNAL_ORG');
        $result = $this->terminus("env:info {$site}.dev --format=json");

        $this->assertTrue(
            $result->isSuccess(),
            "Command Should return clean status"
        );
        $testSiteInfo = json_decode(
            $result->__toString(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertEquals($org,
            $testSiteInfo['organization'], "test site org for known value");
        $this->assertEquals($site,
            $testSiteInfo['name'], "Test site name for known value.");
    }

    /**
     * @test
     */
    public function testEnvUpdate()
    {
        $this->fail();
    }

     /**
      * @test
      */
    public function testEnvDelete()
    {
        $this->fail();
    }
}
