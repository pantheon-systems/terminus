<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class LoginCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     * @vcr auth_login
     */
    public function authLoginCommandLogsInWithMachineToken()
    {
//        $this->setInput([
//          'command' => 'auth:login',
//          ['machine-token' => '111111111111111111111111111111111111111111111',]
//        ]);
//        $this->assertEquals('[notice] Logging in via machine token.', $this->runCommand()->fetchTrimmedOutput());
//        $this->assertEquals(0, $this->getStatusCode());
    }

    /**
     * @test
     * @vcr auth_login_machine-token_invalid
     */
    public function authLoginCommandFailsToLogInWithInvalidMachineToken()
    {
//        $this->setInput([
//          'command' => 'auth:login',
//          ['machine-token' => 'invalid',]
//        ]);
//        $this->assertEquals(
//            '[error]  The provided machine token is not valid.',
//            $this->runCommand()->fetchTrimmedOutput()
//        );
//        $this->assertEquals(1, $this->getStatusCode());
    }
}
