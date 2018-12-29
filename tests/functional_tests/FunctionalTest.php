<?php

use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
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
     * Test to see if we can use terminus.phar and get rational results
     * back from the Hermes API.
     */
    public function testSiteInfo()
    {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $output = $this->terminus("site:info $site --format=yaml");

        $this->assertContains('framework: wordpress', $output);
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
        list($output, $status) = static::call_terminus($command);
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
        exec("$project_dir/terminus.phar " . $command, $output, $status);
        $output = implode("\n", $output);

        return [$output, $status];
    }
}
