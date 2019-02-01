<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\WipeCommand;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class WipeCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\WipeCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class WipeCommandTest extends EnvCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new WipeCommand();
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
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
}
