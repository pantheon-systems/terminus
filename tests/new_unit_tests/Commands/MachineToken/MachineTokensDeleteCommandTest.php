<?php
namespace Pantheon\Terminus\UnitTests\Commands\Auth;
use Pantheon\Terminus\Commands\MachineToken\DeleteCommand;
use Pantheon\Terminus\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Terminus\Models\MachineToken;

/**
 * Testing class for Pantheon\Terminus\Commands\Auth\LoginCommand
 */
class MachineTokenDeleteCommandTest extends MachineTokenCommandTest
{

  /**
   * Sets up the fixture, for example, open a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    parent::setUp();

    $this->command = new DeleteCommand(new Config());
    $this->command->setSession($this->session);
    $this->command->setLogger($this->logger);

    // Ignore user input.
    $input = $this->getMockBuilder(ArrayInput::class)
      ->disableOriginalConstructor()
      ->getMock();
    $input->expects($this->any())
      ->method('hasParameterOption')
      ->with(['--yes', '-y'])
      ->willReturn(true);

    $this->command->setInput($input);
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
      ->willReturn(null);

    $this->setExpectedException(\Exception::class, 'There are no machine tokens with the id {id}.');

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
      ->willReturn(
        ['status_code' => 500]
      );

    $this->machine_tokens->expects($this->once())
      ->method('get')
      ->with($this->equalTo('123'))
      ->willReturn(
        $token
      );


    $this->setExpectedException(\Exception::class, 'There was an problem deleting the machine token.');

    $this->command->delete('123');
  }
}
