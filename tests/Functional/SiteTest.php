<?php

namespace Pantheon\Terminus\Tests\Functional;

use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
    /**
     * If there is a terminus token, then log in.
     */
    public static function setUpBeforeClass()
    {
        $token = getenv('TERMINUS_TOKEN');
        if ($token) {
            static::call_terminus("auth:login --machine-token=$token");
        }
    }

    /**
     * @test
     * Test to see if we can use terminus.phar and get rational results
     * back from the Hermes API.
     */
    public function testSiteInfo()
    {
        $sitename = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $output = $this->terminus("site:info {$sitename} --format=yaml");
        $this->assertContains('framework: wordpress', $output);
    }

    /**
     * @test
     * Test to see if we can use terminus.phar and get rational results
     * back from the Hermes API.
     */
    public function testSiteCreate()
    {
        $sitename = uniqid('terminus-test-');
        $this->terminus(
            "site:create {$sitename} {$sitename} drupal9  --yes --org=5ae1fa30-8cc4-4894-8ca9-d50628dcba17",

        );
        $siteInfo = json_decode(
            $this->terminus("site:info {$sitename} -- format=json"),
            true,
            JSON_THROW_ON_ERROR
        );
        $this->assertIsArray($siteInfo, "Response from newly-created site should be unserializable json");
        $this->assertArrayHasKey('id', $siteInfo, "Response from newly-created site should contain an ID property");
        $result = $this->terminus(
            "site:delete {$sitename} --yes --format=json"
        );
    }

    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     * @param integer $status The required status code for the
     *   provided command
     */
    protected function terminus($command, $expected_status = 0)
    {
        [$output, $status] = static::call_terminus($command);
        $this->assertEquals($expected_status, $status, $output);

        return $output;
    }

    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     */
    protected static function call_terminus($command)
    {
        $project_dir = dirname(dirname(__DIR__));
        exec(
            sprintf("%s/%s %s", $project_dir, TERMINUE_BIN_FILE, $command, ),
            $output,
            $status
        );
        $output = implode("\n", $output);

        return [$output, $status];
    }
}
