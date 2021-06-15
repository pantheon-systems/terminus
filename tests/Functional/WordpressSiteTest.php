<?php

namespace Pantheon\Terminus\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * Class WordpressSiteTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class WordpressSiteTest extends TestCase
{
    /**
     * @var string
     */
    protected $testSitename;

    /**
     *
     */
    public function setUp()
    {
        $this->testSitename = \uniqid('terminus-test-');
        $token = getenv('TERMINUS_TOKEN');
        if ($token) {
            static::call_terminus("auth:login --machine-token=$token");
        }
        $this->terminus(
            "site:create {$this->testSitename} {$this->testSitename} "
            . "wordpress  --yes --org=5ae1fa30-8cc4-4894-8ca9-d50628dcba17",
        );
    }

    /**
     * @test
     */
    public function testSiteInfo()
    {
        $response = $this->terminus("site:info {$this->testSitename} --format=yaml");
        if (is_array($response)) {
            $response = join("", $response);
        }
        $siteInfo = json_decode(
            $response,
            true,
            JSON_THROW_ON_ERROR
        );
        $this->assertIsArray(
            $siteInfo,
            "Response from newly-created site should be unserialized json"
        );
        $this->assertArrayHasKey(
            'id',
            $siteInfo,
            "Response from newly-created site should contain an ID property"
        );
        $this->assertEquals(
            $this->testSitename,
            $siteInfo['name'],
            "Site info name should equal generated test name"
        );
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->terminus(
            "site:delete {$this->testSitename} --yes"
        );
    }
}
