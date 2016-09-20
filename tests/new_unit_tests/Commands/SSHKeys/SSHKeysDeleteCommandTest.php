<?php
namespace Pantheon\Terminus\UnitTests\Commands;


use Pantheon\Terminus\Commands\SSHKey\DeleteCommand;
use Pantheon\Terminus\Config;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\SshKey;

/**
 * Testing class for Pantheon\Terminus\Commands\Auth\LoginCommand
 */
class SSHKeysDeleteCommandTest extends SSHKeysCommandTest
{

  /**
   * Sets up the fixture, for example, open a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    parent::setUp();

    $this->command = new DeleteCommand($this->getConfig());
    $this->command->setSession($this->session);
    $this->command->setLogger($this->logger);
  }


  /**
   * Tests the ssh-keys:delete command.
   *
   * @return void
   */
  public function testSSHKeysDelete()
  {
    $token = $this->getMockBuilder(SshKey::class)
      ->disableOriginalConstructor()
      ->getMock();

    $token->expects($this->exactly(2))
      ->method('delete')
      ->willReturn(
        ['status_code' => 200]
      );


    $this->ssh_keys->expects($this->exactly(2))
      ->method('get')
      ->with($this->equalTo('79e7e210bdf335bb8651a46b9a8417ab'))
      ->willReturn(
        $token
      );


    $this->command->delete('79e7e210bdf335bb8651a46b9a8417ab');
    $this->command->delete('79:e7:e2:10:bd:f3:35:bb:86:51:a4:6b:9a:84:17:ab');
  }

  /**
   * Tests the ssh-keys:delete command when there are no tokens.
   *
   * @return void
   */
  public function testSSHKeysDeleteNonExistant()
  {
    $token = $this->getMockBuilder(SshKey::class)
      ->disableOriginalConstructor()
      ->getMock();

    $token->expects($this->never())
      ->method('delete');

    $this->ssh_keys->expects($this->once())
      ->method('get')
      ->with($this->equalTo('123'))
      ->will($this->throwException(new TerminusException));


    $this->setExpectedException(TerminusException::class);

    $this->command->delete('123');
  }

  /**
   * Tests the ssh-keys:delete command when the API fails.
   *
   * @return void
   */
  public function testSSHKeysDeleteAPIFailure()
  {
    $token = $this->getMockBuilder(SshKey::class)
      ->disableOriginalConstructor()
      ->getMock();

    $token->expects($this->once())
      ->method('delete')
      ->will($this->throwException(new TerminusException('There was an problem deleting the SSH key.')));


    $this->ssh_keys->expects($this->once())
      ->method('get')
      ->with($this->equalTo('123'))
      ->willReturn(
        $token
      );


    $this->setExpectedException(\Exception::class, 'There was an problem deleting the SSH key.');

    $this->command->delete('123');
  }
}
