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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new WipeCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    /**
     * Tests the env:wipe command
     */
    public function testWipe()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $message = 'successful workflow';

        $this->expectConfirmation();

        $this->workflow->expects($this->once())
          ->method('checkProgress')
          ->with()
          ->willReturn(true);
        $this->workflow->expects($this->once())
          ->method('getMessage')
          ->with()
          ->willReturn($message);
        $this->site->expects($this->any())
          ->method('get')
          ->willReturn(null);
        $this->environment->expects($this->once())
          ->method('wipe')
          ->willReturn($this->workflow);

        $this->logger->expects($this->at(0))
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo('Wiping the "{env}" environment of "{site}"')
          );
        $this->logger->expects($this->at(1))
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo($message)
          );

        $out = $this->command->wipe("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests the env:wipe command when the confirmation is declined
     *
     * @todo Remove this when removing TerminusCommand::confirm()
     */
    public function testWipeConfirmationDecline()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';

        $this->expectConfirmation(false);
        $this->workflow->expects($this->never())
          ->method('checkProgress');
        $this->workflow->expects($this->never())
          ->method('getMessage');
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(false);
        $this->environment->expects($this->never())
          ->method('wipe');
        $this->logger->expects($this->never())
          ->method('log');

        $out = $this->command->wipe("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }
}
