<?php

namespace Pantheon\Terminus\UnitTests\Commands\SSHKey;

use Pantheon\Terminus\Commands\SSHKey\RemoveCommand;
use Pantheon\Terminus\Models\SSHKey;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\SSHKey\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\SSHKey
 */
class RemoveCommandTest extends SSHKeyCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }


    /**
     * Tests the ssh-keys:delete command
     */
    public function testSSHKeysDelete()
    {
        $token = $this->getMockBuilder(SSHKey::class)
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

        $out = $this->command->delete('79e7e210bdf335bb8651a46b9a8417ab');
        $this->assertNull($out);

        $out2 = $this->command->delete('79:e7:e2:10:bd:f3:35:bb:86:51:a4:6b:9a:84:17:ab');
        $this->assertNull($out2);
    }

    /**
     * Tests the ssh-keys:delete command when there are no tokens
     */
    public function testSSHKeysDeleteNonExistant()
    {
        $token = $this->getMockBuilder(SSHKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->never())
            ->method('delete');

        $this->ssh_keys->expects($this->once())
            ->method('get')
            ->with($this->equalTo('123'))
            ->will($this->throwException(new TerminusException));


        $this->setExpectedException(TerminusException::class);

        $out = $this->command->delete('123');
        $this->assertNull($out);
    }

    /**
     * Tests the ssh-keys:delete command when the API fails
     */
    public function testSSHKeysDeleteAPIFailure()
    {
        $token = $this->getMockBuilder(SSHKey::class)
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
        
        $this->setExpectedException(
            \Exception::class,
            'There was an problem deleting the SSH key.'
        );

        $out = $this->command->delete('123');
        $this->assertNull($out);
    }
}
