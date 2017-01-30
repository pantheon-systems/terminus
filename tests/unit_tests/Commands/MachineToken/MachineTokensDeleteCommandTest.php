<?php
namespace Pantheon\Terminus\UnitTests\Commands\MachineToken;

use Pantheon\Terminus\Commands\MachineToken\DeleteCommand;
use Robo\Config;
use Pantheon\Terminus\Models\MachineToken;
use Pantheon\Terminus\Exceptions\TerminusException;
use Symfony\Component\Console\Input\Input;

/**
 * Class MachineTokenDeleteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Auth\LoginCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class MachineTokenDeleteCommandTest extends MachineTokenCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->command = new DeleteCommand(new Config());
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
        $token = $this->getMockBuilder(MachineToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->once())
            ->method('delete')
            ->willReturn(
                ['status_code' => 200]
            );


        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->willReturn(
                $token
            );

        $this->command->delete('123');
    }

    /**
     * Tests the machine-token:delete command when there are no tokens.
     *
     * @return void
     */
    public function testMachineTokenDeleteNonExistant()
    {
        $token = $this->getMockBuilder(MachineToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->never())
            ->method('delete');

        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->will($this->throwException(new TerminusException));


        $this->setExpectedException(TerminusException::class);

        $this->command->delete('123');
    }

    /**
     * Tests the machine-token:delete command when the API fails.
     *
     * @return void
     */
    public function testMachineTokenDeleteAPIFailure()
    {
        $token = $this->getMockBuilder(MachineToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new TerminusException('There was an problem deleting the machine token.')));


        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->willReturn(
                $token
            );


        $this->setExpectedException(
            \Exception::class,
            'There was an problem deleting the machine token.'
        );

        $this->command->delete('123');
    }
}
