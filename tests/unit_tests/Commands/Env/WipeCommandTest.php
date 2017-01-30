<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\WipeCommand;

/**
 * Class WipeCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\WipeCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class WipeCommandTest extends EnvCommandTest
{
    /**
     * Tests the env:wipe command
     */
    public function testWipe()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $message = 'successful workflow';

        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);
        $this->site->method('get')->willReturn($site_name);
        $this->environment->expects($this->once())
            ->method('wipe')
            ->willReturn($this->workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Wiping the "{env}" environment of "{site}"'),
                $this->equalTo(['site' => $site_name, 'env' => $this->environment->id,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo($message)
            );

        $this->command = new WipeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);

        $out = $this->command->wipe("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }
}
