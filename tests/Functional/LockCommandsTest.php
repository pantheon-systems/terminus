<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class LockCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class LockCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Lock\DisableCommand
     * @covers \Pantheon\Terminus\Commands\Lock\EnableCommand
     * @covers \Pantheon\Terminus\Commands\Lock\InfoCommand
     *
     * @group lock
     * @group todo
     *
     * @throws \Exception
     */
    public function testLockCommands()
    {
        // Disable the lock if set.
        $disableLockCommand = sprintf('lock:disable %s.%s', $this->getSiteName(), 'dev');
        $this->terminus($disableLockCommand);

        // Verify the lock is disabled.
        $getLockInfoCommand = sprintf('lock:info %s.%s ', $this->getSiteName(), 'dev');
        $lockInfo = $this->terminusJsonResponse($getLockInfoCommand);
        $expectedLockInfo = [
            'locked' => false,
            'username' => null,
            'password' => null,
        ];
        $this->assertEquals($expectedLockInfo, $lockInfo);

        // Enable the lock.
        $lockUserName = 'test_user';
        $lockPassword = 'test_password';
        $enableLockInfoCommand = sprintf(
            'lock:enable %s.%s %s %s',
            $this->getSiteName(),
            'dev',
            $lockUserName,
            $lockPassword
        );
        $this->terminus($enableLockInfoCommand);

        // Verify the lock is enabled.
        $lockInfo = $this->terminusJsonResponse($getLockInfoCommand);
        $expectedLockInfo = [
            'locked' => true,
            'username' => $lockUserName,
            'password' => $lockPassword,
        ];
        $this->assertEquals($expectedLockInfo, $lockInfo);

        // Disable the lock.
        $this->terminus($disableLockCommand);
    }
}
