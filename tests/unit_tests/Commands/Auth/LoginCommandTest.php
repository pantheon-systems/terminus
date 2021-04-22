<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Commands\Auth\LoginCommand;
use Pantheon\Terminus\Models\SavedToken;

/**
 * Class LoginCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Auth\LoginCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class LoginCommandTest extends AuthTest
{
    protected $tokens;
    /**
     * @var SavedToken
     */
    private $token;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->token = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->token->expects($this->any())
            ->method('session')
            ->willReturn($this->session);

        $this->tokens = $this->getMockBuilder(SavedTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->expects($this->any())
            ->method('getTokens')
            ->willReturn($this->tokens);

        $this->command = new LoginCommand();
        $this->command->setConfig($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the auth:login command where the machine token is explicitly given
     */
    public function testLogInWithMachineToken()
    {
        $token_string = 'token_string';

        $this->tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo($token_string))
            ->will($this->throwException(new \Exception));
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );
        $this->tokens->expects($this->once())
            ->method('create')
            ->with($this->equalTo($token_string));

        $out = $this->command->logIn(['machine-token' => $token_string,]);
        $this->assertNull($out);
    }

    /**
     * Tests the auth:login command where the email address referencing a saved machine token is given
     */
    public function testLogInWithEmail()
    {
        $email = "email@ddr.ess";

        $this->tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->willReturn($this->token);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );
        $this->token->expects($this->once())
            ->method('logIn')
            ->with();

        $out = $this->command->logIn(compact('email'));
        $this->assertNull($out);
    }

    /**
     * Tests the auth:login command when no info is given but a single machine token has been saved
     */
    public function testLogInWithSoloSavedToken()
    {
        $email = "email@ddr.ess";

        $this->tokens->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->token,]);
        $this->token->expects($this->once())
            ->method('get')
            ->with($this->equalTo('email'))
            ->willReturn($email);
        $this->logger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [
                    $this->equalTo('notice'),
                    $this->equalTo('Found a machine token for {email}.'),
                    $this->equalTo(compact('email'))
                ],
                [
                    $this->equalTo('notice'),
                    $this->equalTo('Logging in via machine token.')
                ]
            );
        $this->token->expects($this->once())
            ->method('logIn');

        $out = $this->command->logIn();
        $this->assertNull($out);
    }

    /**
     * Tests the auth:login command when no data was given and there are no saved machine tokens
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Please visit the dashboard to generate a machine token:
     */
    public function testCannotLogInWithoutTokens()
    {
        $this->tokens->expects($this->once())
            ->method('all')->willReturn([]);

        $out = $this->command->logIn();
        $this->assertNull($out);
    }

    /**
     * Tests the auth:login command when no data was given and there are multiple saved machine tokens
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Tokens were saved for the following email addresses:
     */
    public function testCannotLogInWithoutIndicatingWhichToken()
    {
        $this->tokens->expects($this->once())
            ->method('all')
            ->willReturn([$this->token, $this->token,]);
        $this->tokens->expects($this->once())
            ->method('ids')
            ->willReturn(['token1', 'token2',]);

        $out = $this->command->logIn();
        $this->assertNull($out);
    }
}
