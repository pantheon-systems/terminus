<?php

namespace Pantheon\Terminus\UnitTests\Commands\SSHKey;

use Pantheon\Terminus\Commands\SSHKey\AddCommand;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\SSHKey\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\SSHKey
 */
class AddCommandTest extends SSHKeyCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the ssh-key:add command
     */
    public function testSSHKeysAdd()
    {
        $file = 'some_file';

        $this->ssh_keys->expects($this->once())
            ->method('addKey')
            ->with($this->equalTo($file));
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Added SSH key from file {file}.'),
                $this->equalTo(compact('file'))
            );

        $out = $this->command->add($file);
        $this->assertNull($out);
    }
}
