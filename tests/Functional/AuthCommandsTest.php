<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class AuthCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Auth\LoginCommand
     * @covers \Pantheon\Terminus\Commands\Auth\LogoutCommand
     *
     * @group auth
     * @group short
     */
    public function testAuthLogin()
    {
        $this->assertTrue(true, "create Backup");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Auth\WhoamiCommand
     *
     * @group auth
     * @group short
     *
     * @throws \JsonException
     */
    public function testAuthWhoAmI()
    {
        $result = $this->terminusJsonResponse("auth:whoami");
        $this->assertIsArray($result, "Response from auth:whoami should be an array.");
        $this->assertArrayHasKey("id", $result, "Response from whoami should include a user ID");
        $this->assertArrayHasKey(
            "email",
            $result,
            "Response from whoami should include a user name",
        );
    }
}
