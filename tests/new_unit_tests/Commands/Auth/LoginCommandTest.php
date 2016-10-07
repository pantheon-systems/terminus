<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Commands\Auth\LoginCommand;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Exceptions\TerminusException;

class LoginCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->command = new LoginCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function testAuthLoginCommandLogsInWithMachineToken()
    {
        $token = '111111111111111111111111111111111111111111111';

        $this->session->expects($this->once())
            ->method('logInViaMachineToken')
            ->with($this->equalTo($token));

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );


        $this->command->logIn(['machine-token' => $token,]);
    }

    /**
     * @test
     */
    public function testAuthLoginCommandLogsInWithEmailMachineToken()
    {
        $email = 'test@example.com';

        $this->session->expects($this->once())
            ->method('logInViaSavedEmailMachineToken')
            ->with($this->equalTo($email));


        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Found a machine token for {email}.'),
                $this->equalTo(compact('email'))
            );
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );

        $this->command->logIn(['email' => $email,]);
    }

    /**
     * @test
     */
    public function testAuthLoginCommandLogsInWithNoArgs()
    {
        $this->setExpectedExceptionRegExp(
            TerminusException::class,
            "/Please visit the dashboard to generate a machine token/"
        );

        $this->command->logIn([]);
    }
}
