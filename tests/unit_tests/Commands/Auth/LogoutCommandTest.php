<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Commands\Auth\LogoutCommand;
use Pantheon\Terminus\Models\SavedToken;

/**
 * Class LogoutCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Auth\LogoutCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class LogoutCommandTest extends AuthTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new LogoutCommand();
        $this->command->setConfig($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the auth:logout command
     */
    public function testLogInWithMachineToken()
    {
        $token = $this->getMockBuilder(SavedToken::class)
          ->disableOriginalConstructor()
          ->getMock();
        $token->expects($this->once())
          ->method('delete');

        $token2 = $this->getMockBuilder(SavedToken::class)
          ->disableOriginalConstructor()
          ->getMock();
        $token2->expects($this->once())
          ->method('delete');

        $tokens = $this->getMockBuilder(SavedTokens::class)
          ->disableOriginalConstructor()
          ->setMethods(['all'])
          ->getMock();
        $tokens->expects($this->any())
          ->method('all')
          ->willReturn([$token, $token2,]);

        $this->session->expects($this->any())
          ->method('getTokens')
          ->willReturn($tokens);
        $this->session->expects($this->once())
          ->method('destroy');


        $this->logger->expects($this->once())
          ->method('log')
          ->with(
              $this->equalTo('notice'),
              $this->equalTo('Your saved machine tokens have been deleted and you have been logged out.')
          );

        $out = $this->command->logOut();
        $this->assertNull($out);
    }
}
