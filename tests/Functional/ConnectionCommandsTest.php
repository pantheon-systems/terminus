<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionCommandsTest.
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
     * @group short
     */
    public function testConnectionCommands()
    {
        $info = $this->terminusJsonResponse(
            sprintf('connection:info %s', $this->getSiteEnv())
        );
        $this->assertIsArray($info);
        $this->assertArrayHasKey('sftp_command', $info, 'Returned data should have sftp command.');
        $envInfo = $this->terminusJsonResponse(sprintf('env:info %s', $this->getSiteEnv()));
        $this->assertIsArray($envInfo);
        $this->assertArrayHasKey(
            'connection_mode',
            $envInfo,
            'Returned ENV info should have a "connection_mode" property'
        );

        $modeToSet = $envInfo['connection_mode'] === 'git' ? 'sftp' : 'git';
        $this->terminus(sprintf('connection:set %s %s', $this->getSiteEnv(), $modeToSet));
        $newEnvInfo = $this->terminusJsonResponse(sprintf('env:info %s', $this->getSiteEnv()));
        $this->assertEquals(
            $modeToSet,
            $newEnvInfo['connection_mode'],
            'Connection mode should return set value'
        );
    }
}
