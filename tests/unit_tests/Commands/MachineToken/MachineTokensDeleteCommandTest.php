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
     * @var Token
     */
    protected $token;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->token = $this->getMockBuilder(MachineToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new DeleteCommand(new Config());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    /**
     * Tests the machine-token:delete command.
     */
    public function testMachineTokenDelete()
    {
        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->willReturn($this->token);
        $this->expectConfirmation();
        $this->token->expects($this->once())
            ->method('delete')
            ->willReturn(['status_code' => 200,]);

        $out = $this->command->delete('123');
        $this->assertNull($out);
    }

    /**
     * Tests the machine-token:delete command when there are no tokens.
     */
    public function testMachineTokenDeleteNonExistant()
    {
        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->will($this->throwException(new TerminusException));
        $this->token->expects($this->never())
            ->method('delete');

        $this->setExpectedException(TerminusException::class);

        $out = $this->command->delete('123');
        $this->assertNull($out);
    }

    /**
     * Tests the machine-token:delete command when the API fails.
     */
    public function testMachineTokenDeleteAPIFailure()
    {
        $this->machine_tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->willReturn($this->token);
        $this->expectConfirmation();
        $this->token->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new TerminusException('There was an problem deleting the machine token.')));

        $this->setExpectedException(
            \Exception::class,
            'There was an problem deleting the machine token.'
        );

        $out = $this->command->delete('123');
        $this->assertNull($out);
    }
}
