<?php
namespace Pantheon\Terminus\UnitTests\Commands\MachineToken;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Commands\MachineToken\DeleteAllCommand;
use Pantheon\Terminus\Models\SavedToken;
use Robo\Config;

/**
 * Class MachineTokenDeleteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Auth\LoginCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class MachineTokenDeleteAllCommandTest extends MachineTokenCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new DeleteAllCommand(new Config());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    /**
     * Tests the machine-token:delete command.
     *
     * @return void
     */
    public function testMachineTokenDelete()
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
          ->setMethods(['all',])
          ->getMock();
        $tokens->expects($this->any())
          ->method('all')
          ->willReturn([$token, $token2]);

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

        $this->command->deleteAll();
    }
}
