<?php

namespace Pantheon\Terminus\Tests\Functional;

use PHPUnit\Framework\TestCase;

class DrupalSiteTest extends TestCase
{

    protected $testSitename;

    public function setUp()
    {
        $this->testSitename = \uniqid('terminus-test-');
        $token = getenv('TERMINUS_TOKEN');
        if ($token) {
            static::call_terminus("auth:login --machine-token=$token");

        }
        $this->terminus(
            "site:create {$this->testSitename} {$this->testSitename} "
            ."drupal9  --yes --org=5ae1fa30-8cc4-4894-8ca9-d50628dcba17",
        );
    }

    public function tearDown()
    {
        $this->terminus(
            "site:delete {$this->testSitename} --yes"
        );
    }


    /**
     * @test
     * Test to see if we can use terminus.phar and get rational results
     * back from the Hermes API.
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
        $this->assertIsArray($siteInfo,
            "Response from newly-created site should be unserialized json");
        $this->assertArrayHasKey('id', $siteInfo,
            "Response from newly-created site should contain an ID property");
        $this->assertEquals($this->testSitename, $siteInfo['name'],
            "Site info name should equal generated test name");
    }

    /**
     *
     */

    /**
     * @test
     * Test to see if we can use terminus.phar and get rational results
     * back from the Hermes API.
     */
    public function testEnvCreateInfoDelete()
    {


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
