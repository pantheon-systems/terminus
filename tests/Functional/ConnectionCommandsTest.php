<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class ConnectionCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Connection\InfoCommand
     * @covers \Pantheon\Terminus\Commands\Connection\SetCommand
     * @covers \Pantheon\Terminus\Commands\Env\InfoCommand
     *
     * @group connection
     * @group long
     *
     * @throws \JsonException
     */
    public function testConnection()
    {
        $sitename = $this->getSiteName();
        $info = $this->terminusJsonResponse(
            "connection:info {$sitename}.dev"
        );
        $this->assertIsArray(
            $info,
            "returned data should be an array"
        );
        $this->assertArrayHasKey(
            "sftp_command",
            $info,
            "returned data should have sftp command."
        );
        $env_info = $this->terminusJsonResponse(
            "env:info {$sitename}.dev"
        );
        $this->assertIsArray(
            $env_info,
            "Assert Returned Env Info is array"
        );
        $this->assertArrayHasKey(
            "connection_mode",
            $env_info,
            "Returned ENV info should have a 'connection_mode' property"
        );
        $mode_to_set = ($env_info['connection_mode'] == "git") ? "sftp" : "git";
        $this->terminus("connection:set {$sitename}.dev {$mode_to_set}");
        $new_env_info = $this->terminusJsonResponse(
            "env:info {$sitename}.dev"
        );
        $this->assertEquals(
            $mode_to_set,
            $new_env_info['connection_mode'],
            "Connection mode should return set value"
        );
    }
}
